<?php


namespace Siruis\Agent\Connections;


use Bloatless\WebSocket\Client;
use DateTime;
use Exception;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusRPCError;
use Siruis\Errors\Exceptions\SiriusTimeoutRPC;
use Siruis\Helpers\StringHelper;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;
use Siruis\RPC\Futures\Future;

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
                $expirationTime = date("Y-m-d h:i:s", time() + $this->timeout);
                $expirationTime = new DateTime($expirationTime);
            } else {
                $expirationTime = null;
            }
            $future = new Future($this->tunnel_rpc, $expirationTime);
            $request = build_request($msg_type, $future, $params ? $params : []);
            $msg_typ = Type::fromString($msg_type);
            $encrypt = $msg_typ->protocol;
            if (!$this->tunnel_rpc->post($request, $encrypt)) {
                throw new SiriusRPCError();
            }
            if ($waitResponse) {
                $success = $future->wait($this->timeout);
                if ($success) {
                    if ($future->hasException()) {
                        $future->throwException();
                    } else {
                        return $future->getValue();
                    }
                } else {
                    throw new SiriusTimeoutRPC();
                }
            }

        } catch (SiriusConnectionClosed | Exception $exception) {
            if ($reconnectOnError) {
                $this->reopen();
                return $this->remoteCall($msg_type, $params, $waitResponse, false);
            } else {
                throw;
            }
        }
    }

    public function sendMessage(
        Message $message, $their_vk, string $endpoint,
        ?string $my_vk, array $routing_keys, bool $coprotocol = false,
        bool $ignore_errors = false
    ): ?Message
    {
        if ($this->connector->isOpen()) {
            throw new SiriusConnectionClosed('Open agent connection at first');
        }
        if (is_string($their_vk)) {
            $recipient_verkeys = [$their_vk];
        } else {
            $recipient_verkeys = $their_vk;
        }
        $params = [
            'message' => $message,
            'routing_keys' => $routing_keys ? $routing_keys : [],
            'recipient_verkey' => $recipient_verkeys,
            'sender_verkey' => $my_vk
        ];
        if ($this->preferAgentSide) {
            $params['timeout'] = $this->timeout;
            $params['endpoint_address'] = $endpoint;
            $arr_remote_call = $this->remoteCall('did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/send_message', $params);
        } else {
            $wired = $this->remoteCall(
                'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prepare_message_for_send',
                $params
            );
            if (StringHelper::startsWith($endpoint, 'ws://') || StringHelper::startsWith($endpoint, 'wss://')) {
                $ws = $this->getWebsocket($endpoint);
                $ws->sendData($wired);
                $ok = true;
                $body = b'';
            } else {
                
            }
        }
    }

    public function reopen()
    {
        $this->connector->reconnect();
        $payload = $this->connector->read(1);
        $context = Message::deserialize($payload);
        $this->setup($context);
    }

    public function getWebsocket(string $url): Client
    {
        $tup = $this->websockets[$url];
        if (!$tup) {
            $session = new Client();
            $url_parsed = parse_url($url);
            $session->connect($url_parsed['host'], $url_parsed['port'], $url_parsed['path']);
            $this->websockets[$url] = $session;
        } else {
            if (!$tup->checkConnection()) {
                $tup->reconnect();
                $this->websockets[$url] = $tup;
            }
        }
        return $tup;
    }
}