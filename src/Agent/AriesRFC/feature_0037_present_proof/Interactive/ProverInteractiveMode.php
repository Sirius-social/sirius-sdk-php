<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Interactive;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\PresentationMessage;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\PresentProofProblemReport;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\RequestPresentationMessage;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Hub\Coprotocols\AbstractP2PCoProtocol;
use Siruis\Hub\Init;

class ProverInteractiveMode
{
    public const PROPOSE_NOT_ACCEPTED = "propose_not_accepted";
    public const RESPONSE_NOT_ACCEPTED = "response_not_accepted";
    public const RESPONSE_PROCESSING_ERROR = "response_processing_error";
    public const REQUEST_NOT_ACCEPTED = "request_not_accepted";
    public const RESPONSE_FOR_UNKNOWN_REQUEST = "response_for_unknown_request";
    public const REQUEST_PROCESSING_ERROR = 'request_processing_error';
    public const VERIFY_ERROR = 'verify_error';

    /**
     * @var string
     */
    protected $my_did;
    /**
     * @var string
     */
    protected $pool_name;
    /**
     * @var string
     */
    protected $master_secret_id;
    /**
     * @var AbstractP2PCoProtocol
     */
    protected $coprotocol;
    /**
     * @var array|null
     */
    protected $self_attested_identity;
    /**
     * @var string
     */
    protected $default_value;

    protected $thread_id;

    protected $version;

    /**
     * ProverInteractiveMode constructor.
     * @param string $my_did
     * @param string $pool_name
     * @param string $master_secret_id
     * @param \Siruis\Hub\Coprotocols\AbstractP2PCoProtocol $co
     * @param array|null $self_attested_identity
     * @param string $default_value
     */
    public function __construct(
        string $my_did, string $pool_name, string $master_secret_id, AbstractP2PCoProtocol $co,
        array $self_attested_identity = null, $default_value = ''
    )
    {
        $this->my_did = $my_did;
        $this->pool_name = $pool_name;
        $this->master_secret_id = $master_secret_id;
        $this->coprotocol = $co;
        $this->self_attested_identity = $self_attested_identity;
        $this->default_value = $default_value;
        $this->thread_id = null;
        $this->version = '1.0';
    }

    /**
     * Fetch request correspondent data from Wallet
     *
     * @param \Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\RequestPresentationMessage $request  Verifier request
     * @param array|null $extra_query Wallet extra-query
     * @param int $limit_referents max num of fetching creds
     * @return \Siruis\Agent\AriesRFC\feature_0037_present_proof\Interactive\SelfIdentity
     */
    public function fetch(RequestPresentationMessage $request, array $extra_query = null, $limit_referents = 1): SelfIdentity
    {
        $self_identity = new SelfIdentity();
        $self_identity->load(
            $this->self_attested_identity,
            $request->getProofRequest(),
            $extra_query,
            $limit_referents,
            $this->default_value
        );
        if ($request->getPleaseAck()) {
            $this->thread_id = $request->getAckMessageId();
        } else {
            $this->thread_id = $request->getId();
        }
        $this->version = $request->getVersion();
        return $self_identity;
    }

    /**
     * @param \Siruis\Agent\AriesRFC\feature_0037_present_proof\Interactive\SelfIdentity $identity
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function prove(SelfIdentity $identity): array
    {
        if (!$identity->getIsFilled()) {
            $problem_report = new PresentProofProblemReport(
                ['problemCode' => self::REQUEST_PROCESSING_ERROR, 'explain' => 'No proof correspondent to proof-request']
            );
            $this->coprotocol->send($problem_report);
            return [false, null];
        }
        $schemas = [];
        $credential_defs = [];
        $rev_states = [];
        $all_infos = [];
        $opts = new CacheOptions();
        $requested_credentials = [
            'self_attested_attributes' => [],
            'requested_attributes' => [],
            'requested_predicates' => []
        ];
        // Stage-1: self attested attributes
        foreach ($identity->self_attested_attributes as $referent_id => $self_attest_attr) {
            $requested_credentials['self_attested_attributes'][$referent_id] = $self_attest_attr->getValue();
        }
        // Stage-2: requested attributes
        foreach ($identity->requested_attributes as $referent_id => $attr_variants) {
            $selected_variants = [];
            foreach ($attr_variants as $variant) {
                if ($variant->is_selected()) {
                    $selected_variants[] = $variant;
                }
            }
            $selected_variant = $selected_variants[0];
            $info = [
                'cred_id' => $selected_variant->getCredInfo()['referent_id'],
            ];
            $requested_credentials['requested_attributes'][$referent_id] = $info;
            $all_infos[] = $selected_variant->getCredInfo();
        }
        // Stage-3: requested predicates
        foreach ($identity->requested_predicates as $referent_id => $pred_variants) {
            $selected_predicates = [];
            foreach ($pred_variants as $pred) {
                if ($pred->is_selected()) {
                    $selected_predicates[] = $pred;
                }
            }
            $selected_predicate = $selected_predicates[0];
            $info = [
                'cred_id' => $selected_predicate->getCredInfo()['referent_id']
            ];
            if ($selected_predicate->revealed) {
                $info['revealed'] = true;
            }
            $requested_credentials['requested_attributes'][$referent_id] = $info;
            $all_infos[] = $selected_predicate->getCredInfo();
        }
        // Stage-4: fill other data
        foreach ($all_infos as $cred_info) {
            $schema_id = $cred_info['schema_id'];
            $cred_def_id = $cred_info['cred_def_id'];
            $schema = Init::Cache()->get_schema(
                $this->pool_name, $this->my_did, $cred_def_id, $opts
            );
            $cred_def = Init::Cache()->get_cred_def(
                $this->pool_name, $this->my_did, $cred_def_id, $opts
            );
            $schemas[$schema_id] = $schema;
            $credential_defs[$cred_def_id] = $cred_def;
        }
        // Stage-5: Build Proof
        $proof = Init::AnonCreds()->prover_create_proof(
            $identity->getProofRequest(),
            $requested_credentials,
            $this->master_secret_id,
            $schemas,
            $credential_defs,
            $rev_states
        );
        $presentation_msg = new PresentationMessage([], $proof, null, null, ['version' => $this->version]);
        $presentation_msg->setPleaseAck(true);
        if ($this->thread_id) {
            $presentation_msg->setThreadId($this->thread_id);
        }
        // Switch to Verifier
        [$ok, $resp] = $this->coprotocol->switch($presentation_msg);
        if ($ok) {
            if ($resp instanceof Ack) {
                return [true, null];
            }

            if ($resp instanceof PresentProofProblemReport) {
                return [false, $resp];
            }

            $problem_report = new PresentProofProblemReport(
                ['problemCode' => self::RESPONSE_FOR_UNKNOWN_REQUEST, 'explain' => "Unexpected response @type: ". $resp->getType()]
            );
            $this->coprotocol->send($problem_report);
            return [false, $problem_report];
        }

        $problem_report = new PresentProofProblemReport(
            ['problem_code' => self::RESPONSE_PROCESSING_ERROR, 'explain' => 'Response awaiting terminated by timeout']
        );
        $this->coprotocol->send($problem_report);
        return [false, $problem_report];
    }
}