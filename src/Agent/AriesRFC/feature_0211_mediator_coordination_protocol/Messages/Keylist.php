<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


class Keylist extends CoordinateMediationMessage
{
    public $NAME = 'keylist';

    public const NAME = 'keylist';

    public function __construct(array $payload,
                                array $keys,
                                int $count = null,
                                int $offset  = null,
                                int $remaining = null,
                                ...$args)
    {
        parent::__construct($payload, ...$args);
        $recipient_keys = [];
        foreach ($keys as $key) {
            array_push($recipient_keys, ['recipient_key' => $key]);
        }
        $this->payload['keys'] = $recipient_keys;
        if (!is_null($count) && !is_null($offset) && !is_null($remaining)) {
            $this->payload['pagination'] = [];
            $this->payload['pagination']['count'] = $count;
            $this->payload['pagination']['offset'] = $offset;
            $this->payload['pagination']['remaining'] = $remaining;
        }
        self::registerMessageClass(Keylist::class, $this->PROTOCOL, $this->NAME);
    }
}