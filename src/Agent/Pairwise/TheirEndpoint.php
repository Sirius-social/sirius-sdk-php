<?php


namespace Siruis\Agent\Pairwise;


class TheirEndpoint
{
    /**
     * @var string
     */
    public $endpoint;
    /**
     * @var string
     */
    public $verkey;
    /**
     * @var array
     */
    public $routing_keys;

    /**
     * TheirEndpoint constructor.
     * @param string|null $endpoint
     * @param string|null $verkey
     * @param array|null $routing_keys
     */
    public function __construct(?string $endpoint,
                                ?string $verkey,
                                ?array $routing_keys = null)
    {

        $this->endpoint = $endpoint;
        $this->verkey = $verkey;
        $this->routing_keys = $routing_keys ?: [];
    }

    /**
     * Get net_loc attribute.
     *
     * @return string|null
     */
    public function getNetloc(): ?string
    {
        if ($this->endpoint) {
            return parse_url($this->endpoint)['host'];
        }

        return null;
    }

    /**
     * Set net_loc attribute.
     *
     * @param string $value
     * @return void
     */
    public function setNetLoc(string $value): void
    {
        if ($this->endpoint) {
            $components = parse_url($this->endpoint);
            $components['host'] = $value;
            $this->endpoint = http_build_url('', $components);
        }
    }
}