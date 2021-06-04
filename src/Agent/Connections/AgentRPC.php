<?php


namespace Siruis\Agent\Connections;

use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Siruis\Agent\Transport;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusRPCError;
use Siruis\Errors\Exceptions\SiriusTimeoutRPC;
use Siruis\Helpers\ArrayHelper;
use Siruis\Helpers\StringHelper;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;
use Siruis\RPC\Futures\Future;
use Siruis\RPC\Parsing;
use Siruis\RPC\Tunnel\AddressedTunnel;
use stdClass;
use WebSocket\Client;

class AgentRPC extends BaseAgentConnection
{
    const EXPIRATION_OFF = true;
    /**
     * @var null
     */
    public $tunnel_rpc;
    /**
     * @var null
     */
    public $tunnel_coprotocols;
    /**
     * @var array
     */
    public $endpoints;
    /**
     * @var array
     */
    public $networks;
    /**
     * @var array
     */
    public $websockets;
    /**
     * @var bool
     */
    public $preferAgentSide;

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
        return '/rpc';
    }

    public function remoteCall(
        string $msg_type,
        array $params = null,
        bool $waitResponse = true,
        bool $reconnectOnError = true
    ) {
        try {
            $expirationTime = null;
            if (!$this->connector->isOpen()) {
                throw new SiriusConnectionClosed('Open agent connection at first');
            }
            if ($this->timeout && !self::EXPIRATION_OFF) {
                $expirationTime = date("Y-m-d h:i:s", time() + $this->timeout);
                $expirationTime = DateTime::createFromFormat('Y-m-d H:i:s', $expirationTime);
            } else {
                $expirationTime = null;
            }
            $future = new Future($this->tunnel_rpc, $expirationTime);
            $request = Parsing::build_request($msg_type, $future, $params ? $params : []);
            $msg_typ = Type::fromString($msg_type);
            $encrypt = !in_array($msg_typ->protocol, ['admin', 'microledgers', 'microledgers-batched']);
            $this->tunnel_rpc->post($request, $encrypt);
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
                error_log($exception->getMessage());
                throw $exception;
            }
        }
    }

    /**
     * @param Message $message
     * @param $their_vk
     * @param string $endpoint
     * @param string|null $my_vk
     * @param array $routing_keys
     * @param bool $coprotocol
     * @param bool $ignore_errors
     * @return Message|null
     * @throws SiriusConnectionClosed
     * @throws SiriusRPCError
     * @throws GuzzleException
     */
    public function sendMessage(
        Message $message,
        $their_vk,
        string $endpoint,
        ?string $my_vk,
        array $routing_keys,
        bool $coprotocol = false,
        bool $ignore_errors = false
    ): ?Message {
        if (!$this->connector->isOpen()) {
            throw new SiriusConnectionClosed('Open agent connection at first');
        }
        if (is_string($their_vk)) {
            $recipient_verkeys = [$their_vk];
        } else {
            $recipient_verkeys = $their_vk;
        }
        $params = [
            'message' => $message->payload,
            'routing_keys' => $routing_keys ? $routing_keys : [],
            'recipient_verkeys' => $recipient_verkeys,
            'sender_verkey' => $my_vk
        ];
        if ($this->preferAgentSide) {
            $params = array_merge($params, ['timeout' => $this->timeout, 'endpoint_address' => $endpoint]);
            list($ok, $body) = $this->remoteCall('did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/send_message', $params);
        } else {
            $wired = $this->remoteCall(
                'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prepare_message_for_send',
                $params
            );
            if (StringHelper::startsWith($endpoint, 'ws://') || StringHelper::startsWith($endpoint, 'wss://')) {
                $ws = $this->getWebsocket($endpoint);
                $ws->binary($wired);
                $ok = true;
                $body = b'';
            } else {
                list($ok, $body) = Transport::http_send($wired, $endpoint, $this->timeout);
            }
        }
        if (!$ok) {
            if (!$ignore_errors) {
                throw new SiriusRPCError($body);
            }
        } else {
            if ($coprotocol) {
                return $this->read_protocol_message();
            } else {
                return null;
            }
        }
    }

    public function send_message_batched(Message $message, array $batches): array
    {
        if (!$this->connector->isOpen()) {
            throw new SiriusConnectionClosed('Open agent connection at first');
        }
        $params = [
            'message' => $message,
            'timeout' => $this->timeout,
            'batches' => $batches
        ];
        return $this->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/send_message_batched',
            $params
        );
    }

    public function read_protocol_message(): Message
    {
        return $this->tunnel_coprotocols->receive($this->timeout);
    }

    public function start_protocol_with_threading(string $thid, int $ttl = null)
    {
        $this->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/start_protocol',
            [
                'thid' => $thid,
                'channel_address' => $this->tunnel_coprotocols->address,
                'ttl' => $ttl
            ]
        );
    }

    public function start_protocol_with_threads(array $threads, int $ttl = null)
    {
        $this->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/start_protocol',
            [
                'threads' => $threads,
                'channel_address' => $this->tunnel_coprotocols->address,
                'ttl' => $ttl
            ]
        );
    }

    public function start_protocol_for_p2p(
        string $sender_verkey,
        string $recipient_verkey,
        array $protocols,
        int $ttl = null
    ) {
        $this->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/start_protocol',
            [
                'sender_verkey' => $sender_verkey,
                'recipient_verkey' => $recipient_verkey,
                'protocols' => $protocols,
                'channel_address' => $this->tunnel_coprotocols->address,
                'ttl' => $ttl
            ]
        );
    }

    public function stop_protocol_with_threading(string $thid, bool $off_response = false)
    {
        $this->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/stop_protocol',
            [
                'thid' => $thid,
                'off_response' => $off_response
            ],
            !$off_response
        );
    }

    public function stop_protocol_with_threads(array $threads, bool $off_response = false)
    {
        $this->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/stop_protocol',
            [
                'threads' => $threads,
                'off_response' => $off_response
            ],
            !$off_response
        );
    }

    public function stop_protocol_for_p2p(
        string $sender_verkey,
        string $recipient_verkey,
        array $protocols,
        bool $off_response = false
    ) {
        $this->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/stop_protocol',
            [
                'sender_verkey' => $sender_verkey,
                'recipient_verkey' => $recipient_verkey,
                'protocols' => $protocols,
                'off_response' => $off_response
            ],
            !$off_response
        );
    }

    public function setup(Message $context)
    {
        $context = $context->payload;
        $proxies = $context['~proxy'];
        $channel_rpc = null;
        $channel_sub_protocol = null;
        foreach ($proxies as $proxy) {
            if ($proxy['id'] == 'reverse') {
                $channel_rpc = $proxy['data']['json']['address'];
            } elseif ($proxy['id'] == 'sub-protocol') {
                $channel_sub_protocol = $proxy['data']['json']['address'];
            }
        }
        if (!$channel_rpc) {
            throw new RuntimeException('rpc channel is empty');
        }
        if (!$channel_sub_protocol) {
            throw new RuntimeException('sub-protocol channel is empty');
        }
        $this->tunnel_rpc = new AddressedTunnel(
            $channel_rpc,
            $this->connector,
            $this->connector,
            $this->p2p
        );
        $this->tunnel_coprotocols = new AddressedTunnel(
            $channel_sub_protocol,
            $this->connector,
            $this->connector,
            $this->p2p
        );
        // Extract active endpoints
        $endpoints = $context['~endpoints'] ? $context['~endpoints'] : [];
        $endpoint_collection = [];
        foreach ($endpoints as $endpoint) {
            $body = $endpoint['data']['json'];
            $address = $body['address'];
            $frontend_key = ArrayHelper::getValueWithKeyFromArray('frontend_routing_key', $body);
            if ($frontend_key) {
                $routing_keys = $body['routing_keys'] ? $body['routing_keys'] : null;
                foreach ($routing_keys as $routing_key) {
                    $is_default = $routing_key['is_default'];
                    $key = $routing_key['routing_key'];
                    array_push($endpoint_collection, new Endpoint($address, [$key, $frontend_key], $is_default));
                }
            } else {
                array_push($endpoint_collection, new Endpoint($address, [], false));
            }
        }
        if (!$endpoint_collection) {
            throw new RuntimeException('Endpoints are empty');
        }
        $this->endpoints = $endpoint_collection;
        $this->networks = $context['~networks'] ? $context['~networks'] : [];
    }

    public function reopen()
    {
        $this->connector->reconnect();
        $payload = $this->connector->read(1);
        $context = Message::deserialize($payload);
        $this->setup($context);
    }

    public function close()
    {
        parent::close();
        foreach ($this->websockets as $websocket) {
            $websocket->close();
        }
    }

    public function getWebsocket(string $url): Client
    {
        $tup = $this->websockets[$url];
        if (!$tup) {
            $urlParsed = parse_url($url);
            if ($urlParsed['scheme'] == 'http') {
                $url = 'ws://' . $urlParsed['host'];
            } elseif ($urlParsed['scheme'] == 'https') {
                $url = 'wss://' . $urlParsed['host'];
            }
            $session = new Client($url);
            $session->ping();
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
