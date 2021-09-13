<?php


namespace Siruis\Agent\AriesRFC\feature_0211_mediator_coordination_protocol\Messages;


class KeylistUpdate extends CoordinateMediationMessage
{
    public $NAME = 'keylist-update';

    public const NAME = 'keylist-update';

    /**
     * KeylistUpdate constructor.
     * @param array $payload
     * @param string $endpoint
     * @param KeylistAddAction[]|KeylistRemoveAction[] $updates
     * @param mixed ...$args
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function __construct(array $payload, string $endpoint, array $updates, ...$args)
    {
        parent::__construct($payload, ...$args);
        $this->payload['endpoint'] = $endpoint;
        $this->payload['updates'] = $updates;
        self::registerMessageClass(KeylistUpdate::class, $this->PROTOCOL, $this->NAME);
    }
}