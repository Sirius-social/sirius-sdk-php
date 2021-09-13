<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


use Siruis\Agent\AriesRFC\feature_0095_basic_message\Messages\Message;
use Siruis\Agent\Base\AriesProtocolMessage;

class CoordinateMediationMessage extends AriesProtocolMessage
{
    public $PROTOCOL = 'coordinate-mediation';

    public const PROTOCOL = 'coordinate-mediation';

    public function __construct(array $payload, ...$args)
    {
        parent::__construct($payload, ...$args);
        Message::registerMessageClass(CoordinateMediationMessage::class, $this->PROTOCOL, $this->NAME);
    }
}