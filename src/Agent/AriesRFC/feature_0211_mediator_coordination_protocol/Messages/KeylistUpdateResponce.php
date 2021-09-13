<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


class KeylistUpdateResponce extends CoordinateMediationMessage
{
    public $NAME = 'keylist-update-responce';

    public const NAME = 'keylist-update-responce';

    /**
     * KeylistUpdateResponce constructor.
     * @param array $payload
     * @param KeylistAddAction[]|KeylistRemoveAction[] $updated
     * @param mixed ...$args
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function __construct(array $payload, array $updated, ...$args)
    {
        parent::__construct($payload, ...$args);
        $this->payload['updated'] = $updated;
        self::registerMessageClass(KeylistUpdateResponce::class, $this->PROTOCOL, $this->NAME);
    }
}