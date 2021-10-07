<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\StateMachines;


use Siruis\Agent\AriesRFC\feature_0015_acks\Status;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\BaseIssueCredentialMessage;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\CredentialAck;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\IssueCredentialMessage;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\IssueProblemReport;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\OfferCredentialMessage;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\ProposedAttrib;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\RequestCredentialMessage;
use Siruis\Agent\Ledgers\Ledger;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;
use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Errors\Exceptions\StateMachineTerminatedWithError;
use Siruis\Errors\IndyExceptions\WalletItemNotFound;
use Siruis\Hub\Init;

/**
 * Implementation of Holder role for Credential-issuing protocol
 * @see https://github.com/hyperledger/aries-rfcs/tree/master/features/0036-issue-credential
 */
class Holder extends BaseIssuingStateMachine
{
    /**
     * @var Pairwise
     */
    protected $issuer;

    public function __construct(Pairwise $issuer, int $time_to_live = 60, $logger = null)
    {
        parent::__construct($time_to_live, $logger);
        $this->issuer = $issuer;
    }

    public function accept(
        OfferCredentialMessage $offer, string $master_secret_id, string $comment = null,
        string $locale = BaseIssueCredentialMessage::DEF_LOCALE, Ledger $ledger = null
    ): array
    {
        $doc_uri = $offer->getDocUri();
        $this->coprotocol($this->issuer);
        try {
            $offer_msg = $offer;
            try {
                $offer_msg->validate();
            } catch (SiriusValidationError $error) {
                error_log($error->getMessage());
                // throw new StateMachineTerminatedWithError(self::REQUEST_NOT_ACCEPTED, $error->getMessage());
            }

            // Step-1: Process Issuer Offer
            list($_, $offer_body, $cred_def_body) = $offer_msg->parse(true);
            if (!$offer_body) {
                throw new StateMachineTerminatedWithError(
                    self::OFFER_PROCESSING_ERROR, 'Error while parsing cred_offer', true
                );
            }
            if (!$cred_def_body) {
                if ($ledger) {
                    $cred_def = $ledger->load_cred_def(
                        $offer_body['cred_def_id'], $this->issuer->me->did
                    );
                    $cred_def_body = $cred_def->body;
                }
            }
            if (!$cred_def_body) {
                throw new StateMachineTerminatedWithError(
                    self::OFFER_PROCESSING_ERROR, 'Error while parsing cred_def', true
                );
            }
            list($cred_request, $cred_metadata) = Init::AnonCreds()->prover_create_credential_req(
                $this->issuer->me->did, $offer_body, $cred_def_body, $master_secret_id
            );

            // Step-2: Send request to Issuer
            $request_msg = new RequestCredentialMessage(
                [], $comment, $cred_request, $locale, null, $offer->getVersion(), $doc_uri
            );

            if ($offer->getPleaseAck()) {
                $request_msg->setThreadId($offer->getAckMessageId());
            } else {
                $request_msg->setThreadId($offer->getId());
            }
            // Switch to await participant action
            $resp = $this->switch($request_msg, [IssueCredentialMessage::class]);
            if (!$resp instanceof IssueCredentialMessage) {
                throw new StateMachineTerminatedWithError(
                    self::REQUEST_NOT_ACCEPTED, 'Unexpected @type: '.(string)$resp->getType()
                );
            }

            $issue_msg = $resp;
            try {
                $issue_msg->validate();
            } catch (SiriusValidationError $error) {
                throw new StateMachineTerminatedWithError(
                    self::REQUEST_NOT_ACCEPTED, $error->getMessage()
                );
            }

            // Step-3: Store credential
            $cred_id = $this->_store_credential(
                $cred_metadata, $issue_msg->getCred(), $cred_def_body, null, $issue_msg->getCredId()
            );
            $this->_store_mime_types($cred_id, $offer->getPreview());
            $thread_id = $issue_msg->getPleaseAck() ? $issue_msg->getAckMessageId() : $issue_msg->getId();
            $ack = new CredentialAck(
                [], null, $offer->getVersion(), $doc_uri, $thread_id, Status::OK
            );
            $this->send($ack);
        } catch (StateMachineTerminatedWithError $error) {
            $this->problem_report = new IssueProblemReport(
                [], null, null, $doc_uri, $error->problem_code, $error->explain,
            );
            $this->log([
                'progress' => 100, 'message' => 'Terminated with error',
                'problem_code' => $error->problem_code, 'explain' => $error->explain
            ]);
            if ($error->notify) {
                $this->send($this->problem_report);
            }
            return [false, null];
        }

        return [true, $cred_id];
    }

    public function _is_leader(): bool
    {
        return false;
    }

    public function _store_credential(
        array $cred_metadata, array $cred, array $cred_def, ?array $rev_reg_def, ?string $cred_id
    )
    {
        try {
            $cred_older = Init::AnonCreds()->prover_get_credential($cred_id);
        } catch (WalletItemNotFound $exception) {
            $cred_older = null;
        }
        if ($cred_older) {
            // Delete older credential
            Init::AnonCreds()->prover_delete_credential($cred_id);
        }
        return Init::AnonCreds()->prover_store_credential(
            $cred_id,
            $cred_metadata,
            $cred,
            $cred_def,
            $rev_reg_def
        );
    }

    /**
     * @param string $cred_id
     * @param ProposedAttrib[] $preview
     * @throws \SodiumException
     */
    public static function _store_mime_types(string $cred_id, array $preview)
    {
        if (!is_null($preview)) {
            $mime_types = [];
            foreach ($preview as $prop_attrib) {
                if (key_exists('mime-type', $prop_attrib->data)) {
                    array_push($mime_types, [$prop_attrib->data['name'] => $prop_attrib['mime-type']]);
                }
            }
            if (count($mime_types) > 0) {
                $record = self::get_mime_types($cred_id);
                if ($record) {
                    Init::NonSecrets()->delete_wallet_record('mime-types', $cred_id);
                }
                Init::NonSecrets()->add_wallet_record('mime-types', $cred_id, Encryption::bytes_to_b64(json_encode($mime_types)));
            }
        }
    }

    /**
     * @param string $cred_id
     * @return array|mixed
     * @throws \SodiumException
     */
    public static function get_mime_types(string $cred_id)
    {
        try {
            $record = Init::NonSecrets()->get_wallet_record('mime-types', $cred_id, new RetrieveRecordOptions(true, true, false));
        } catch (WalletItemNotFound $exception) {
            $record = null;
        }
        if (!is_null($record)) {
            return json_decode(Encryption::b64_to_bytes($record['value']));
        } else {
            return [];
        }
    }
}