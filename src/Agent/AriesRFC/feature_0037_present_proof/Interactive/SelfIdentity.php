<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Interactive;


use Siruis\Hub\Init;

class SelfIdentity
{
    /**
     * @var string[]|SelfAttestedAttribute[]
     */
    public $self_attested_attributes;
    /**
     * @var string[]|CredAttribute[]
     */
    public $requested_attributes;
    /**
     * @var string[]|CredAttribute[]
     */
    public $requested_predicates;
    /**
     * @var array
     */
    protected $non_processed;
    /**
     * @var bool
     */
    protected $__mute;
    /**
     * @var mixed
     */
    protected $__proof_request;

    /**
     * SelfIdentity constructor.
     */
    public function __construct()
    {
        $this->self_attested_attributes = [];
        $this->requested_attributes = [];
        $this->requested_predicates = [];
        $this->non_processed = [];
        $this->__mute = false;
        $this->__proof_request = null;
    }

    /**
     * Get proof_request property.
     *
     * @return mixed|null
     */
    public function getProofRequest()
    {
        return $this->__proof_request;
    }

    /**
     * @param array $self_attested_identity
     * @param array $proof_request
     * @param array|null $extra_query
     * @param int $limit_referents
     * @param string $default_value
     * @return bool
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function load(
        array $self_attested_identity, array $proof_request, array $extra_query = null,
        int $limit_referents = 1, $default_value = ''
    ): bool
    {
        $this->clear();
        $this->__proof_request = $proof_request;
        // Stage-1: load self attested attributes
        foreach ($this->getProofRequest()['requested_attributes'] as $referent_id => $data) {
            $restrictions = $data['restrictions'];
            if (!$restrictions && array_key_exists('name', $data)) {
                $attr_name = $data['name'];
                $attr_value = $self_attested_identity[$attr_name] ?? $default_value;
                $this->self_attested_attributes[$referent_id] = new SelfAttestedAttribute(
                    $referent_id, $attr_name, $attr_value
                );
            }
        }
        // Stage-2: load proof-response
        $proof_response = Init::AnonCreds()->prover_search_credentials_for_proof_req(
            $proof_request, $extra_query, $limit_referents
        );
        // Stage-3: fill requested attributes
        foreach ($proof_response['requested_attributes'] as $referent_id => $cred_infos) {
            if (count($cred_infos) > $limit_referents) {
                $cred_infos = array_slice($cred_infos, 0, $limit_referents);
            }
            if (!array_key_exists($referent_id, $this->self_attested_attributes)) {
                if ($cred_infos) {
                    $attr_name = $proof_request['requested_attributes']['name'];
                    $attr_variants = $this->requested_attributes[$referent_id];
                    foreach (array_values($cred_infos) as $i => $item) {
                        $cred_attrib = new CredAttribute(
                            " $referent_id:$i", $item['cred_info'], true, $attr_name, on_cred_attrib_select(),
                        );
                        if (!$attr_variants) {
                            $cred_attrib->setSelected(true);
                        }
                        $attr_variants[] = $referent_id;
                        $this->requested_predicates[$referent_id] = $attr_variants;
                    }
                } else {
                    $this->non_processed[] = $referent_id;
                }
            }
        }
        return $this->getIsFilled();
    }

    public function getIsFilled(): bool
    {
        return count($this->non_processed) === 0;
    }

    public function on_cred_attrib_select(CredAttribute $emitter): void
    {
        // Save from recursion calls
        if ($this->__mute) {
            return;
        }
        // Fire
        $this->__mute = true;
        try {
            [$referent_id] = explode(':', $emitter->uid);
            $neighbours = $this->requested_attributes[$referent_id] ?? $this->requested_predicates[$referent_id];
            foreach ($neighbours as $neighbour) {
                if ($neighbour->uid !== $emitter->uid && $neighbour->is_selected()) {
                    $neighbour->setSelected(false);
                }
            }
        } finally {
            $this->__mute = false;
        }
    }

    public function clear(): void
    {
        $this->self_attested_attributes = [];
        $this->requested_attributes = [];
        $this->requested_predicates = [];
        $this->non_processed = [];
        $this->__proof_request = null;
    }
}