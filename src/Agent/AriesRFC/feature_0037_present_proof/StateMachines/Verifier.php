<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\StateMachines;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0015_acks\Status;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\AttribTranslation;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\BasePresentProofMessage;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\PresentationAck;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\PresentationMessage;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\PresentProofProblemReport;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\RequestPresentationMessage;
use Siruis\Agent\Ledgers\Ledger;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Errors\Exceptions\StateMachineAborted;
use Siruis\Errors\Exceptions\StateMachineTerminatedWithError;
use Siruis\Hub\Init;

/**
 * Implementation of Verifier role for present-proof protocol
 * @see https://github.com/hyperledger/aries-rfcs/tree/master/features/0037-present-proof
 */
class Verifier extends BaseVerifyStateMachine
{
    public $prover;
    public $pool_name;
    public $requested_proof;
    protected $__revealed_attrs;

    public function __construct(Pairwise $prover, Ledger $ledger, int $time_to_live = 60, $logger = null)
    {
        parent::__construct($time_to_live, $logger);
        $this->prover = $prover;
        $this->pool_name = $ledger->name;
        $this->requested_proof = null;
        $this->__revealed_attrs = null;
    }

    public function getRequestedProof()
    {
        return $this->requested_proof;
    }

    public function getRevealedAttrs()
    {
        return $this->__revealed_attrs;
    }

    /**
     * @param array $proof_request
     * @param AttribTranslation[]|null $translation
     * @param string|null $comment
     * @param string $locale
     * @param string|null $proto_version
     * @return bool
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     * @throws StateMachineAborted
     */
    public function verify(array $proof_request,
                           array $translation = null,
                           string $comment = null,
                           string $locale = BasePresentProofMessage::DEF_LOCALE,
                           string $proto_version = null)
    {
        $this->getCoprotocol($this->prover);
        try {
            // Step-1: Send proof request
            $expires_time = date('d-m-Y H:i:s', time() + $this->time_to_live);
            $request_msg = new RequestPresentationMessage(
                [],
                $proof_request,
                $comment,
                $translation,
                $expires_time,
                $locale,
                null,
                $proto_version
            );
            $request_msg->setPleaseAck(true);
            $this->log(['progress' => 30, 'message' => 'Send request', 'payload' => $request_msg->payload]);

            // Switch to await participant action
            $presentation = $this->switch($request_msg, [PresentationMessage::class]);
            if (!$presentation instanceof PresentationMessage) {
                throw new StateMachineTerminatedWithError(
                    self::RESPONSE_NOT_ACCEPTED, 'Unexpected @type: '. $presentation->getType()
                );
            }
            $this->log(['progress' => 60, 'message' => 'Presentation received']);

            // Step-2 Verify
            $identifiers = $presentation->getProof()['identifiers'] ?? [];
            $schemas = [];
            $credential_defs = [];
            $rev_reg_defs = [];
            $rev_regs = [];
            $opts = new CacheOptions();
            foreach ($identifiers as $identifier) {
                $schema_id = $identifier['schema_id'];
                $cred_def_id = $identifier['cred_def_id'];
                $rev_reg_id = $identifier['rev_reg_id'];
                if ($schema_id && !key_exists($schema_id, $schemas)) {
                    $schemas[$schema_id] = Init::Cache()->get_schema(
                        $this->pool_name, $this->prover->me->did, $schema_id, $opts
                    );
                }
                if ($cred_def_id && !key_exists($cred_def_id, $credential_defs)) {
                    $credential_defs[$cred_def_id] = Init::Cache()->get_cred_def(
                        $this->pool_name, $this->prover->me->did, $cred_def_id, $opts
                    );
                }
            }
            $success = Init::AnonCreds()->verifier_verify_proof(
                $proof_request,
                $presentation->getProof(),
                $schemas,
                $credential_defs,
                $rev_reg_defs,
                $rev_regs
            );
            if ($success) {
                $this->requested_proof = $presentation->getProof()['requested_proof'];
                // Parse response and fill revealed attrs
                $revealed_attrs = [];
                foreach ($this->requested_proof['self_attested_attrs'] as $ref_id => $value) {
                    if (key_exists($ref_id, $proof_request['requested_attributes'])) {
                        if (key_exists('name', $proof_request['requested_attributes'][$ref_id])) {
                            $attr_name = $proof_request['requested_attributes'][$ref_id]['name'];
                            $revealed_attrs[$attr_name] = $value;
                        }
                    }
                }
                foreach ($this->requested_proof['revealed_attrs'] as $ref_id => $data) {
                    if (key_exists($ref_id, $proof_request['requested_attributes'])) {
                        if (key_exists('name', $proof_request['requested_attributes'][$ref_id])) {
                            $attr_name = $proof_request['requested_attributes'][$ref_id]['name'];
                            $revealed_attrs[$attr_name] = $data['raw'];
                        }
                    }
                }
                if ($revealed_attrs) {
                    $this->__revealed_attrs = $revealed_attrs;
                }

                // Send Ack
                $thread_id = $presentation->getPleaseAck() ? $presentation->getAckMessageId() : $presentation->getId();
                $ack = new PresentationAck(
                    [], null, null,  null, $thread_id, Status::OK
                );
                $this->log(['progress' => 100, 'message' => 'Verifying terminated successfully']);
                $this->send($ack);
                return true;
            } else {
                $this->log(['progress' => 100, 'message' => 'Verifying terminated with ERROR']);
                throw new StateMachineTerminatedWithError(self::VERIFY_ERROR, 'Verifying return false');
            }
        } catch (StateMachineTerminatedWithError $exception) {
            $this->problem_report = new PresentProofProblemReport($exception->problem_code, $exception->explain);
            $this->log(['progress' => 100, 'message' => 'Terminated with error', $exception->problem_code, $exception->explain]);
            if ($exception->notify) {
                $this->send($this->problem_report);
            }
            return false;
        }
    }

    public function _is_leader()
    {
        return true;
    }
}