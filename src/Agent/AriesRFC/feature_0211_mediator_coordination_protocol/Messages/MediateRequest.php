<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


class MediateRequest extends CoordinateMediationMessage
{
    public $NAME = 'mediate-request';

    public const NAME = 'mediate-request';

    public function __construct(array $payload, ...$args)
    {
        parent::__construct($payload, $args);
        self::registerMessageClass(MediateRequest::class, $this->PROTOCOL, $this->NAME);
    }
}