<?php


namespace Siruis\Agent\Connections;


use RuntimeException;
use Siruis\Base\WebSocketConnector;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Messaging\Message;

abstract class BaseAgentConnection
{
    const IO_TIMEOUT = 30;
    const MSG_TYPE_CONTEXT = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/context';
    /**
     * @var WebSocketConnector
     */
    protected $connector;
    /**
     * @var P2PConnection
     */
    protected $p2p;
    /**
     * @var int
     */
    protected $timeout;
    /**
     * @var
     */
    protected $loop;

    public function __construct(
        string $server_address,
        $credentials,
        P2PConnection $p2p,
        int $timeout = self::IO_TIMEOUT,
        $loop = null
    )
    {
        $this->connector = new WebSocketConnector($server_address, $this->path(), $credentials, $timeout);
        $this->p2p = $p2p;
        $this->timeout = $timeout;
    }

    abstract public function path();

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int|null $value
     */
    public function setTimeout(?int $value)
    {
        if (!$value) {
            $this->timeout = null;
        } elseif ($value > 0) {
            $this->timeout = $value;
        } else {
            throw new RuntimeException('Timeout must be > 0');
        }
    }

    /**
     * Check communication
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->connector->isOpen();
    }

    /**
     * Close communication
     */
    public function close()
    {
        $this->connector->close();
    }

    /**
     * @param string $server_address
     * @param $credentials
     * @param P2PConnection $p2p
     * @param int $timeout
     * @param null $loop
     *
     * @return mixed
     *
     * @throws SiriusInvalidMessageClass
     */
    public static function create(
        string $server_address,
        $credentials,
        P2PConnection $p2p,
        int $timeout = self::IO_TIMEOUT,
        $loop = null
    )
    {
        $instance = new static($server_address, $credentials, $p2p, $timeout, $loop);
        $instance->connector->open();
        $payload = $instance->connector->read($timeout);
        $context = Message::deserialize($payload);
        $msg_type = key_exists('@type', $context->payload) ? $context->payload['@type'] : null;
        if (!$msg_type) {
            throw new RuntimeException('message @type is empty');
        }

        if ($msg_type !== self::MSG_TYPE_CONTEXT) {
            throw new RuntimeException('message @type is empty');
        }

        $instance->setup($context);
        return $instance;
    }
}