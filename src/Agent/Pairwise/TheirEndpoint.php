<?php


namespace Siruis\Agent\Pairwise;


class TheirEndpoint
{
    public $endpoint;
    public $verkey;
    public $routing_keys;

    public function __construct(string $endpoint,
                                string $verkey,
                                array $routing_keys = null)
    {

        $this->endpoint = $endpoint;
        $this->verkey = $verkey;
        $this->routing_keys = $routing_keys ? $routing_keys : [];
    }

    public function getNetloc(): ?string
    {
        if ($this->endpoint) {
            return parse_url($this->endpoint)['host'];
        } else {
            return null;
        }
    }

    public function setNetLoc(string $value)
    {
        if ($this->endpoint) {
            $components = parse_url($this->endpoint);
            $components['host'] = $value;
            $this->endpoint = http_build_url(null, $components);
        }
    }
}