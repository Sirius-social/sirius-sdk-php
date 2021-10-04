<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\StateMachines;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\PresentationMessage;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\PresentProofProblemReport;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\RequestPresentationMessage;
use Siruis\Agent\Ledgers\Ledger;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Errors\Exceptions\StateMachineTerminatedWithError;
use Siruis\Hub\Init;

/**
 * Implementation of Prover role for present-proof protocol
 * @see https://github.com/hyperledger/aries-rfcs/tree/master/features/0037-present-proof
 */
class Prover extends BaseVerifyStateMachine
{
    /**
     * @var Pairwise
     */
    private $verifier;
    /**
     * @var string
     */
    private $pool_name;

    /**
     * Prover constructor.
     * @param Pairwise $verifier Verifier described as pairwise instance.
     * (Assumed pairwise was established earlier: statically or via connection-protocol)
     * @param Ledger $ledger network (DKMS) name that is used to verify credentials presented by prover
     * (Assumed Ledger was configured earlier - pool-genesis file was uploaded and name was set)
     * @param int $time_to_live
     * @param null $logger
     */
    public function __construct(Pairwise $verifier, Ledger $ledger, int $time_to_live = 60, $logger = null)
    {
        parent::__construct($time_to_live, $logger);
        $this->verifier = $verifier;
        $this->pool_name = $ledger->name;
    }

    public function prove(RequestPresentationMessage $request, string $master_secret_id)
    {
        $this->getCoprotocol($this->verifier);
        try {
            // Step-1: Process proof-request
            $this->log(['progress' => 10, 'Received proof request', 'payload' => $request->payload]);
            try {
                $request->validate();
            } catch (SiriusValidationError $err) {
                throw new StateMachineTerminatedWithError(self::REQUEST_NOT_ACCEPTED, $err->getMessage());
            }
            list($cred_infos, $schemas, $credential_defs, $rev_states) = $this->_extract_credentials_info(
                $request->getProofRequest(), $this->pool_name
            );

            if ($cred_infos['requested_attributes'] || $cred_infos['requested_predicates']) {
                // Step-2: Build proof
                $proof = Init::AnonCreds()->prover_create_proof(
                    $request->getProofRequest(),
                    $cred_infos,
                    $master_secret_id,
                    $schemas,
                    $credential_defs,
                    $rev_states
                );
                // Step-3: Send proof and wait Ack to check success from Verifier side
                $presentation_msg = new PresentationMessage([], $proof, null, null, null, null, $request->getVersion());
                $presentation_msg->setPleaseAck(true);
                if ($request->getPleaseAck()) {
                    $presentation_msg->setThreadId($request->getAckMessageId());
                } else {
                    $presentation_msg->setThreadId($request->getId());
                }

                // Step-3: Wait ACK
                $this->log(['progress' => 50, 'message' => 'Send presentation']);

                // Switch to await participant action
                $ack = $this->switch($presentation_msg, [Ack::class, PresentProofProblemReport::class]);

                if ($ack instanceof Ack) {
                    $this->log(['progress' => 100, 'message' => 'Verify OK!']);
                    return true;
                } elseif ($ack instanceof PresentProofProblemReport) {
                    $this->log(['progress' => 100, 'message' => 'Verify ERROR!']);
                    return false;
                } else {
                    throw new StateMachineTerminatedWithError(
                        self::REQUEST_PROCESSING_ERROR, 'Unexpected response @type: '.(string)$ack->getType()
                    );
                }
            } else {
                throw new StateMachineTerminatedWithError(
                    self::REQUEST_PROCESSING_ERROR, 'No proof correspondent to proof-request'
                );
            }
        } catch (StateMachineTerminatedWithError $error) {
            $this->problem_report = new PresentProofProblemReport(
                $error->problem_code, $error->explain
            );
            $this->log([
                'progress' => 100, 'message' => 'Terminated with error',
                'problem_code' => $error->problem_code, 'explain' => $error->explain
            ]);
            if ($error->notify) {
                $this->send($this->problem_report);
            }
            return false;
        }
    }

    public function _extract_credentials_info($proof_request, string $pool_name)
    {
        $proof_response = Init::AnonCreds()->prover_search_credentials_for_proof_req(
            $proof_request, null, 1
        );
        $schemas = [];
        $credential_defs = [];
        $rev_states = [];
        $opts = new CacheOptions();
        $requested_credentials = [
            'self_attested_attributes' => [],
            'requested_attributes' => [],
            'requested_predicates' => []
        ];
        $all_infos = [];
        foreach ($proof_response['requested_attributes'] as $referent_id => $cred_infos) {
            $cred_info = $cred_infos[0]['cred_info'];
            $info = [
                'cred_id' => $cred_info['referent'],
                'revealed' => true
            ];
            $requested_credentials['requested_attributes'][$referent_id] = $info;
            array_push($all_infos, $cred_info);
        }
        foreach ($proof_response['requested_predicates'] as $referent_id => $predicates) {
            $pred_info = $predicates[0]['cred_info'];
            $info = [
                'cred_id' => $pred_info['referent']
            ];
            $requested_credentials['requested_predicates'][$referent_id] = $info;
            array_push($all_infos, $pred_info);
        }
        foreach ($all_infos as $cred_info) {
            $schema_id = $cred_info['schema_id'];
            $cred_def_id = $cred_info['cred_def_id'];
            $schema = Init::Cache()->get_schema($this->pool_name, $this->verifier->me->did, $schema_id, $opts);
            $cred_def = Init::Cache()->get_cred_def($this->pool_name, $this->verifier->me->did, $cred_def_id, $opts);
            $schemas[$schema_id] = $schema;
            $credential_defs[$cred_def_id] = $cred_def;
        }
        return [$requested_credentials, $schemas, $credential_defs, $rev_states];
    }

    public function _is_leader()
    {
        return false;
    }
}