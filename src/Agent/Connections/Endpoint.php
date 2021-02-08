<?php


namespace Siruis\Agent\Connections;


class Endpoint
{
    public $address;
    public $routingKeys;
    public $isDefault;

    /**
     * Endpoint constructor.
     *
     * @param string $address
     * @param array $routingKeys
     * @param bool $isDefault
     */
    public function __construct(string $address, array $routingKeys, bool $isDefault = false)
    {
        $this->address = $address;
        $this->routingKeys = $routingKeys;
        $this->isDefault = $isDefault;
    }
}