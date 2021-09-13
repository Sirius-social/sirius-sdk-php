<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


class MediateGrant extends CoordinateMediationMessage
{
    public $NAME = 'mediate-grant';

    public const NAME = 'mediate-grant';

    public function __construct(array $payload, ...$args)
    {
        parent::__construct($payload, $args);
        self::registerMessageClass(MediateGrant::class, $this->PROTOCOL, $this->NAME);
    }
}