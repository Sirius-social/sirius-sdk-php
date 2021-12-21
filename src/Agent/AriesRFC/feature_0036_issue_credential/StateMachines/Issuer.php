<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\StateMachines;


use DateTime;
use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\AttribTranslation;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\BaseIssueCredentialMessage;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\CredentialAck;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\IssueCredentialMessage;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\IssueProblemReport;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\OfferCredentialMessage;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\ProposedAttrib;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\RequestCredentialMessage;
use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Codec;
use Siruis\Agent\Ledgers\CredentialDefinition;
use Siruis\Agent\Ledgers\Schema;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\StateMachineTerminatedWithError;
use Siruis\Hub\Init;

/**
 * Implementation of Issuer role for Credential-issuing protocol
 * @see https://github.com/hyperledger/aries-rfcs/tree/master/features/0036-issue-credential
 */
class Issuer extends BaseIssuingStateMachine
{
    /**
     * @var Pairwise 
     */
    protected $holder;

    /**
     * Issuer constructor.
     * @param Pairwise $holder Holder side described as pairwise instance.
     * (Assumed pairwise was established earlier: statically or via connection-protocol)
     * @param int $time_to_live
     * @param null $logger
     */
    public function __construct(Pairwise $holder, int $time_to_live = 60, $logger = null)
    {
        parent::__construct($time_to_live, $logger);
        $this->holder = $holder;
    }

    /**
     * @param array $values credential values {"attr_name": "attr_value"}
     * @param Schema $schema credential schema
     * @param CredentialDefinition $cred_def credential definition prepared and stored in Ledger earlier
     * @param string|null $comment human readable credential comment
     * @param string $locale locale, for example "en" or "ru"
     * @param ProposedAttrib[]|null $preview credential preview
     * @param AttribTranslation[]|null $translation translation of the credential preview according to locale
     * @param string|null $cred_id credential id. Issuer may issue multiple credentials with same cred-id to give holder ability
     *                             to restore old credential
     * @return bool
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     * @throws \Siruis\Errors\Exceptions\StateMachineAborted
     * @throws \SodiumException
     * @throws \Exception
     */
    public function issue(
        array $values, Schema $schema, CredentialDefinition $cred_def,
        string $comment = null, array $preview = null, array $translation = null,
        string $cred_id = null, string $locale = BaseIssueCredentialMessage::DEF_LOCALE
    ): bool
    {
        $this->coprotocol($this->holder);
        try {
            // Step-1: Send offer to holder
            $offer = Init::AnonCreds()->issuer_create_credential_offer($cred_def->getId());
            $expires_time = new DateTime(date('Y-m-d H:i:s', time() + $this->time_to_live));
            $offer_msg = new OfferCredentialMessage(
                [],
                $comment,
                $offer,
                $cred_def->body,
                $preview,
                $schema->body,
                $translation,
                Utils::utc_to_str($expires_time),
                $locale
            );
            $this->log(['progress' => 20, 'message' => 'Send offer', 'payload' => $offer_msg->payload]);

            // Switch to await participant action
            $resp = $this->switch($offer_msg, [RequestCredentialMessage::class]);
            if (!$resp instanceof RequestCredentialMessage) {
                throw new StateMachineTerminatedWithError(
                    self::OFFER_PROCESSING_ERROR, 'Unexpected @type: '. $resp->getType()
                );
            }
            // Step-2: Create credential
            $request_msg = $resp;
            $this->log(['progress' => 40, 'message' => 'Received credential request', 'payload' => $request_msg->payload]);
            $encoded_cred_values = [];
            foreach ($values as $key => $value) {
                $encoded_cred_values[$key] = ['raw' => (string)$value, 'encoded' => Codec::encode($value)];
            }
            $this->log(['progress' => 70, 'message' => 'Build credential with values', 'payload' => $encoded_cred_values]);

            $ret = Init::AnonCreds()->issuer_create_credential(
                $offer,
                $request_msg->getCredRequest(),
                $encoded_cred_values
            );
            [$cred] = $ret;

            // Step-3: Issue and wait Ack
            $issue_msg = new IssueCredentialMessage(
                [], $comment, $cred, $cred_id, $locale
            );
            $this->log(['progress' => 90, 'message' => 'Send Issue message', 'payload' => $issue_msg->payload]);

            $ack = $this->switch($issue_msg, [Ack::class]);
            if ($ack instanceof Ack || $ack instanceof CredentialAck) {
                $this->log(['progress' => 100, 'message' => 'Issuing was terminated successfully']);
                return true;
            }

            throw new StateMachineTerminatedWithError(
                self::ISSUE_PROCESSING_ERROR, 'Unexpected @type: '. $resp->getType()
            );
        } catch (StateMachineTerminatedWithError $error) {
            $this->problem_report = new IssueProblemReport(
                [], null, null,  null, $error->problem_code, $error->explain
            );
            if ($error->notify) {
                $this->send($this->problem_report);
            }
            $this->log([
                'progress' => 100, 'message' => 'Terminated with error',
                'problem_code' => $error->problem_code, 'explain' => $error->explain
            ]);
            return false;
        }
    }

    public function _is_leader(): bool
    {
        return true;
    }
}