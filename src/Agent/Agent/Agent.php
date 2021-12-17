<?php


namespace Siruis\Agent\Agent;

use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Siruis\Agent\Connections\AgentEvents;
use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Connections\BaseAgentConnection;
use Siruis\Agent\Coprotocols\PairwiseCoProtocolTransport;
use Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport;
use Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport;
use Siruis\Agent\Ledgers\Ledger;
use Siruis\Agent\Listener\Listener;
use Siruis\Agent\Microledgers\AbstractMicroledgerList;
use Siruis\Agent\Microledgers\MicroledgerList;
use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\TheirEndpoint;
use Siruis\Agent\Pairwise\WalletPairwiseList;
use Siruis\Agent\Storages\InWalletImmutableCollection;
use Siruis\Agent\Wallet\DynamicWallet;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusRPCError;
use Siruis\Helpers\ArrayHelper;
use Siruis\Messaging\Message;
use Siruis\Storage\Abstracts\AbstractImmutableCollection;

class Agent extends TransportLayers
{
    /**
     * @var string
     */
    public $server_address;
    /**
     * @var string
     */
    public $credentials;
    /**
     * @var P2PConnection
     */
    public $p2p;
    /**
     * @var int
     */
    public $timeout;
    /**
     * @var AbstractImmutableCollection|null
     */
    public $storage;
    /**
     * @var string|null
     */
    public $name;
    /**
     * @var int|SpawnStrategy
     */
    public $spawnStrategy;
    /**
     * @var AgentRPC|null
     */
    public $rpc;
    public $events;
    /**
     * @var DynamicWallet|null
     */
    public $wallet;
    public $endpoints;
    public $ledgers;
    /**
     * @var AbstractPairwiseList|null
     */
    public $pairwise_list;
    /**
     * @var AbstractMicroledgerList|null
     */
    public $microledgers;

    /**
     * Agent constructor.
     * @param string $server_address
     * @param string $credentials
     * @param P2PConnection $p2p
     * @param int $timeout
     * @param AbstractImmutableCollection|null $storage
     * @param string|null $name
     * @param int|null $spawnStrategy
     */
    public function __construct(
        string $server_address,
        string $credentials,
        P2PConnection $p2p,
        int $timeout = BaseAgentConnection::IO_TIMEOUT,
        AbstractImmutableCollection $storage = null,
        string $name = null,
        int $spawnStrategy = null
    ) {
        $parsed = parse_url($server_address);
        if (!in_array($parsed['scheme'], ['https'])) {
            printf('Endpoints has non secure scheme, you will have issues for Android/iOS devices');
        }
        $this->server_address = $server_address;
        $this->credentials = $credentials;
        $this->rpc = null;
        $this->events = null;
        $this->wallet = null;
        $this->timeout = $timeout;
        $this->storage = $storage;
        $this->endpoints = [];
        $this->ledgers = [];
        $this->pairwise_list = null;
        $this->microledgers = null;
        $this->p2p = $p2p;
        $this->name = $name;
        $this->spawnStrategy = SpawnStrategy::PARALLEL;
    }

    public function isOpen(): bool
    {
        return $this->rpc && $this->rpc->isOpen();
    }

    public function ensure_rpc()
    {

    }

    /**
     * @param string $name
     * @return Ledger|null
     */
    public function ledger(string $name)
    {
        $this->__check_is_open();
        return ArrayHelper::getValueWithKeyFromArray($name, $this->ledgers);
    }

    public function spawnTheirEndpoint(string $my_verkey, TheirEndpoint $endpoint): TheirEndpointCoProtocolTransport
    {
        return new TheirEndpointCoProtocolTransport(
            $my_verkey,
            $endpoint,
            $this->__get_RPC()
        );
    }

    public function spawnPairwise(Pairwise $pairwise): PairwiseCoProtocolTransport
    {
        return new PairwiseCoProtocolTransport(
            $pairwise,
            $this->__get_RPC()
        );
    }

    public function spawnThidPairwise(string $thid, Pairwise $pairwise): ThreadBasedCoProtocolTransport
    {
        return new ThreadBasedCoProtocolTransport(
            $thid,
            $pairwise,
            $this->__get_RPC()
        );
    }

    public function spawnThid(string $thid): ThreadBasedCoProtocolTransport
    {
        return new ThreadBasedCoProtocolTransport(
            $thid,
            null,
            $this->__get_RPC()
        );
    }

    public function spawnThidPairwisePthd(string $thid, Pairwise $pairwise, string $pthid): ThreadBasedCoProtocolTransport
    {
        return new ThreadBasedCoProtocolTransport(
            $thid,
            $pairwise,
            $this->__get_RPC(),
            $pthid
        );
    }

    public function spawnThidPthid(string $thid, string $pthid): ThreadBasedCoProtocolTransport
    {
        return new ThreadBasedCoProtocolTransport(
            $thid,
            null,
            $this->__get_RPC(),
            $pthid
        );
    }

    public function open()
    {
        $this->rpc = $this->__new_RPC();
        $this->endpoints = $this->rpc->endpoints;
        $this->wallet = new DynamicWallet($this->rpc);
        if (!$this->storage) {
            $this->storage = new InWalletImmutableCollection($this->wallet->non_secrets);
        }
        foreach ($this->rpc->networks as $network) {
            $this->ledgers[$network] = new Ledger(
                $network,
                $this->wallet->ledger,
                $this->wallet->anoncreds,
                $this->wallet->cache,
                $this->storage
            );
        }
        $this->pairwise_list = new WalletPairwiseList([$this->wallet->pairwise, $this->wallet->did]);
        $this->microledgers = new MicroledgerList($this->rpc);
    }

    public function subscribe(): Listener
    {
        $this->__check_is_open();
        $this->events = AgentEvents::create($this->server_address, $this->credentials, $this->p2p, $this->timeout);
        return new Listener($this->events, $this->pairwise_list);
    }

    public function close()
    {
        if ($this->rpc) {
            $this->rpc->close();
        }
        if ($this->events) {
            $this->events->close();
        }
        $this->wallet = null;
    }

    public function ping(): bool
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/ping_agent'
        );
    }

    /**
     * @param Message $message
     * @param array|string $their_vk
     * @param string $endpoint
     * @param string|null $my_vk
     * @param array|null $routing_keys
     * @throws GuzzleException
     * @throws SiriusConnectionClosed
     * @throws SiriusRPCError
     */
    public function send_message(
        Message $message,
        $their_vk,
        string $endpoint,
        ?string $my_vk,
        ?array $routing_keys = null
    ) {
        $this->__check_is_open();
        $this->rpc->sendMessage($message, $their_vk, $endpoint, $my_vk, $routing_keys, false);
    }

    /**
     * @param Message $message
     * @param Pairwise $to
     * @throws GuzzleException
     * @throws SiriusConnectionClosed
     * @throws SiriusRPCError
     */
    public function send_to(Message $message, Pairwise $to)
    {
        $this->__check_is_open();
        $this->send_message(
            $message,
            $to->their->verkey,
            $to->their->endpoint,
            $to->me->verkey,
            $to->their->routing_keys
        );
    }

    /**
     * Service for QR codes generation
     *
     * You may create PNG image for QR code to share it on Web or others.
     *
     * @param string $value
     * @return mixed
     * @throws SiriusConnectionClosed
     */
    public function generate_qr_code(string $value)
    {
        $this->__check_is_open();
        $resp = $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/admin/1.0/generate_qr',
            ['value' => $value]
        );
        return $resp['url'];
    }

    public function acquire(array $resources, float $lock_timeout, float $enter_timeout = 3): array
    {
        $this->__check_is_open();
        list($success, $busy) = $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/admin/1.0/acquire',
            [
                'names' => $resources,
                'enter_timeout' => $enter_timeout,
                'lock_timeout' => $lock_timeout
            ]
        );
        return [$success, $busy];
    }

    /**
     * Release earlier locked resources
     */
    public function release()
    {
        $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/admin/1.0/release'
        );
    }

    public function reopen(bool $kill_tasks = false)
    {
        $this->__check_is_open();
        $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/reopen',
            ['kill_tasks' => $kill_tasks]
        );
    }

    protected function  __get_RPC(): ?AgentRPC
    {
        if ($this->spawnStrategy = SpawnStrategy::PARALLEL) {
            $rpc = $this->__new_RPC();
        } else {
            $rpc = $this->rpc;
        }
        return $rpc;
    }

    protected function __check_is_open(): array
    {
        if ($this->rpc && $this->rpc->isOpen()) {
            return $this->endpoints;
        } else {
            throw new RuntimeException('Open Agent at first');
        }
    }

    /**
     * @return AgentRPC
     * @throws SiriusInvalidMessageClass
     */
    protected function __new_RPC(): AgentRPC
    {
        return AgentRPC::create(
            $this->server_address,
            $this->credentials,
            $this->p2p,
            $this->timeout
        );
    }
}
