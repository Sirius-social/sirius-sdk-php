<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


use ArrayObject;

class KeylistAddAction extends ArrayObject
{
    /**
     * @var array
     */
    public $payload;

    public function __construct(array $payload, string $recipient_key, string $result = null, ...$args)
    {
        parent::__construct(...$args);
        $this->payload = $payload;
        $this->payload['action'] = 'add';
        $this->payload['recipient_key'] = $recipient_key;
        if (!is_null($result)) {
            $this->payload['result'] = $result;
        }
    }
}