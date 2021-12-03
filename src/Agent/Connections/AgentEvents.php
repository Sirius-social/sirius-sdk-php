<?php


namespace Siruis\Agent\Connections;

use JsonException;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusIOError;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Messaging\Message;

class AgentEvents extends BaseAgentConnection
{
    public const RECONNECT_TRY_COUNT = 2;
    protected $tunnel;
    public $balancingGroup;

    /**
     * AgentEvents constructor.
     * @param string $server_address
     * @param $credentials
     * @param P2PConnection $p2p
     * @param int $timeout
     * @param null $loop
     */
    public function __construct(
        string $server_address,
        $credentials,
        P2PConnection $p2p,
        int $timeout = self::IO_TIMEOUT,
        $loop = null
    ) {
        parent::__construct($server_address, $credentials, $p2p, $timeout, $loop);
        $this->tunnel = null;
        $this->balancingGroup = null;
    }

    /**
     * @param int|null $timeout
     * @return \Siruis\Messaging\Message
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function pull(int $timeout = null): Message
    {
        if (!$this->connector->isOpen()) {
            throw new SiriusConnectionClosed('Open agent connection at first');
        }
        $data = null;
        for ($n = 0; $n < self::RECONNECT_TRY_COUNT; $n++) {
            try {
                $data = $this->connector->read($timeout);
                break;
            } catch (SiriusConnectionClosed | SiriusIOError | SiriusTimeoutIO $exception) {
                $this->reopen();
            }
        }
        if (!$data) {
            throw new SiriusConnectionClosed('agent unreachable');
        }
        try {
            $payload = json_decode(mb_convert_encoding($data, $this->connector->enc), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SiriusInvalidPayloadStructure();
        }
        if (array_key_exists('protected', $payload)) {
            $message = $this->p2p->unpack($payload);
            return new Message(json_decode($message, true, 512, JSON_THROW_ON_ERROR));
        }

        return new Message($payload);
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return '/events';
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     */
    protected function reopen(): void
    {
        $this->connector->reconnect();
        try {
            $payload = $this->connector->read(1);
        } catch (SiriusConnectionClosed | SiriusIOError | SiriusTimeoutIO $e) {
            throw $e;
        }
        $context = Message::deserialize($payload);
        if ($context !== null) {
            $this->setup($context);
        }

        throw new SiriusContextError();
    }

    /**
     * @param Message $context
     * @return void
     */
    public function setup(Message $context): void
    {
        $balancing = $context->payload['~balancing'] ?: [];
        foreach ($balancing as $balance) {
            if ($balance['id'] === 'kafka') {
                $this->balancingGroup = $balance['data']['json']['group_id'];
            }
        }
    }
}
