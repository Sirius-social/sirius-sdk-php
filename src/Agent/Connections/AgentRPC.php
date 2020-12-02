<?php


namespace Siruis\Agent\Connections;


use DateTime;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;

class AgentRPC extends BaseAgentConnection
{
    /**
     * @var null
     */
    protected $tunnel_rpc;
    /**
     * @var null
     */
    protected $tunnel_coprotocols;
    /**
     * @var array
     */
    protected $endpoints;
    /**
     * @var array
     */
    protected $networks;
    /**
     * @var array
     */
    protected $websockets;
    /**
     * @var bool
     */
    protected $preferAgentSide;

    /**
     * AgentRPC constructor.
     * @param string $server_address
     * @param $credentials
     * @param P2PConnection $p2p
     * @param int $timeout
     * @param null $loop
     */
    public function __construct(string $server_address, $credentials, P2PConnection $p2p, int $timeout = self::IO_TIMEOUT, $loop = null)
    {
        parent::__construct($server_address, $credentials, $p2p, $timeout, $loop);
        $this->tunnel_rpc = null;
        $this->tunnel_coprotocols = null;
        $this->endpoints = [];
        $this->networks = [];
        $this->websockets = [];
        $this->preferAgentSide = true;
    }

    public function path()
    {
        // TODO: Implement path() method.
    }

    public function endpoints()
    {
        return $this->endpoints;
    }

    public function networks()
    {
        return $this->networks;
    }

    public function remoteCall(
        string $msg_type,
        array $params = null,
        bool $waitResponse = true,
        bool $reconnectOnError = true
    )
    {
        try {
            $expirationTime = null;
            if (!$this->connector->isOpen()) {
                throw new SiriusConnectionClosed('Open agent connection at first');
            }
            if ($this->timeout) {
                $now = DateTime::createFromFormat('Y-m-d', time());
                $hour = DateTime::createFromFormat('H', time());
                $minute = DateTime::createFromFormat('i', time());
                $now->setTime($hour, $minute, $this->timeout);
                $expirationTime = $now;
            }

        } catch (SiriusConnectionClosed $exception) {

        }
    }
}