<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


class KeylistQuery extends CoordinateMediationMessage
{
    public $NAME = 'keylist-query';

    public const NAME = 'keylist-query';

    public function __construct(array $payload, int $limit = null, int $offset = null, ...$args)
    {
        parent::__construct($payload, ...$args);
        if (!is_null($limit) && !is_null($offset)) {
            $this->payload['paginate'] = [];
            $this->payload['paginate']['limit'] = $limit;
            $this->payload['paginate']['offset'] = $offset;
        }
        self::registerMessageClass(KeylistQuery::class, $this->PROTOCOL, $this->NAME);
    }
}