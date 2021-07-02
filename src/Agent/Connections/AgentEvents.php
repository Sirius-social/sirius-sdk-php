<?php


namespace Siruis\Agent\Connections;

use JsonException;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Messaging\Message;

class AgentEvents extends BaseAgentConnection
{
    const RECONNECT_TRY_COUNT = 2;
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
     * @return Message
     * @throws SiriusConnectionClosed
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function pull(int $timeout = null)
    {
        if (!$this->connector->isOpen()) {
            throw new SiriusConnectionClosed('Open agent connection at first');
        }
        $data = null;
        for ($n = 0; $n < self::RECONNECT_TRY_COUNT; $n++) {
            try {
                $data = $this->connector->read($timeout);
                break;
            } catch (SiriusConnectionClosed $exception) {
                $this->reopen();
            }
        }
        if (!$data) {
            throw new SiriusConnectionClosed('agent unreachable');
        }
        try {
            $payload = json_decode(mb_convert_encoding($data, $this->connector->enc), true);
        } catch (JsonException $exception) {
            throw new SiriusInvalidPayloadStructure();
        }
        if (key_exists('protected', $payload)) {
            $message = $this->p2p->unpack($payload);
            return new Message(json_decode($message, true));
        } else {
            return new Message($payload);
        }
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return '/events';
    }

    /**
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    protected function reopen()
    {
        $this->connector->reconnect();
        $payload = $this->connector->read(1);
        $context = new Message($payload);
        $this->setup($context);
    }

    /**
     * @param Message $context
     */
    public function setup(Message $context)
    {
        $context = $context->payload;
        $balancing = $context['~balancing'] ? $context['~balancing'] : [];
        foreach ($balancing as $balance) {
            if ($balance['id'] == 'kafka') {
                $this->balancingGroup = $balance['data']['json']['group_id'];
            }
        }
    }
}
