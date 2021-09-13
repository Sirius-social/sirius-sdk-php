<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


class MediateDeny extends CoordinateMediationMessage
{
    public $NAME = 'mediate-deny';

    public const NAME = 'mediate-deny';

    /**
     * MediateDeny constructor.
     * @param array $payload
     * @param string $endpoint
     * @param string[] $routing_keys
     * @param mixed ...$args
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function __construct(array $payload, string $endpoint, array $routing_keys, ...$args)
    {
        parent::__construct($payload, $args);
        $this->payload['endpoint'] = $endpoint;
        $this->payload['routing_keys'] = $routing_keys;
        self::registerMessageClass(MediateDeny::class, $this->PROTOCOL, $this->NAME);
    }
}