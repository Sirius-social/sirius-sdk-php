<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Interactive;


class SelfAttestedAttribute
{
    /**
     * @var string
     */
    protected $referent_id;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var mixed
     */
    protected $value;

    /**
     * SelfAttestedAttribute constructor.
     * @param string $referent_id
     * @param string $name
     * @param $value
     */
    public function __construct(string $referent_id, string $name, $value)
    {
        $this->referent_id = $referent_id;
        $this->name = $name;
        $this->value = $value;
    }

    public function getReferentId()
    {
        return $this->referent_id;
    }

    public function getName()
    {
        return $this->name;
    }
}