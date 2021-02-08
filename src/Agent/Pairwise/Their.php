<?php


namespace Siruis\Agent\Pairwise;


class Their extends TheirEndpoint
{
    public $did;
    public $label;
    public $did_doc;
    public function __construct(string $did,
                                string $label,
                                string $endpoint,
                                string $verkey,
                                array $routing_keys = null,
                                array $did_doc = null)
    {
        $this->did = $did;
        $this->label = $label;
        $this->did_doc = $did_doc;
        parent::__construct($endpoint, $verkey, $routing_keys);
    }
}