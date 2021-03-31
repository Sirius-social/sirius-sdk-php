<?php


namespace Siruis\Hub\Core;


use Siruis\Agent\Agent\Agent;
use Siruis\Agent\Agent\SpawnStrategy;
use Siruis\Agent\Connections\BaseAgentConnection;
use Siruis\Agent\Microledgers\AbstractMicroledgerList;
use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Agent\Wallet\Abstracts\AbstractCache;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Agent\Wallet\Abstracts\AbstractDID;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AbstractAnonCreds;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Storage\Abstracts\AbstractImmutableCollection;

class Hub
{
    public static $ROOT_HUB = null;
    public static $THREAD_LOCAL_HUB = null;
    public static $COROUTINE_LOCAL_HUB = null;

    /**
     * @var string
     */
    public $server_uri;
    /**
     * @var string
     */
    public $credentials;
    /**
     * @var P2PConnection
     */
    public $p2p;
    /**
     * @var int|null
     */
    public $timeout;
    /**
     * @var AbstractImmutableCollection|null
     */
    public $storage;
    /**
     * @var AbstractCrypto|null
     */
    public $crypto;
    /**
     * @var AbstractMicroledgerList|null
     */
    public $microledgers;
    /**
     * @var AbstractPairwiseList|null
     */
    public $pairwise_storage;
    /**
     * @var AbstractDID|null
     */
    public $did;
    /**
     * @var AbstractAnonCreds|null
     */
    public $anonCreds;
    /**
     * @var Agent|null
     */
    public $agent;

    public function __construct(
        string $server_uri, string $credentials, P2PConnection $p2p, int $io_timeout = null,
        AbstractImmutableCollection $storage = null, AbstractCrypto $crypto = null,
        AbstractMicroledgerList $microledgers = null, AbstractPairwiseList $pairwise_storage = null,
        AbstractDID $did = null, AbstractAnonCreds $anonCreds = null
    )
    {

        $this->server_uri = $server_uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->timeout = $io_timeout ? $io_timeout : BaseAgentConnection::IO_TIMEOUT;
        $this->storage = $storage;
        $this->crypto = $crypto;
        $this->microledgers = $microledgers;
        $this->pairwise_storage = $pairwise_storage;
        $this->did = $did;
        $this->anonCreds = $anonCreds;
        $this->__create_agent_instance();
    }

    public function copy(): Hub
    {
        $inst = new Hub(
            $this->server_uri, $this->credentials, $this->p2p
        );

        $inst->crypto = $this->crypto;
        $inst->microledgers = $this->microledgers;
        $inst->pairwise_storage = $this->pairwise_storage;
        $inst->did = $this->did;
        return $inst;
    }

    public function abort()
    {

    }

    public function run_soon($coro)
    {

    }

    public function get_agent_connection_lazy(): Agent
    {
        if (!$this->agent->is_open) {
            $this->agent->open();
        }
        return $this->agent;
    }

    public function open()
    {
        $this->get_agent_connection_lazy();
    }

    public function close()
    {
        if ($this->agent->is_open) {
            $this->agent->close();
        }
    }

    public function get_crypto(): AbstractCrypto
    {
        $agent = $this->get_agent_connection_lazy();
        return $this->crypto ? $this->crypto : $agent->wallet->crypto;
    }

    public function get_microledgers(): AbstractMicroledgerList
    {
        $agent = $this->get_agent_connection_lazy();
        return $this->microledgers ? $this->microledgers : $agent->microledgers;
    }

    public function get_pairwise_list(): AbstractPairwiseList
    {
        $agent = $this->get_agent_connection_lazy();
        return $this->pairwise_storage ? $this->pairwise_storage : $agent->pairwise_list;
    }

    public function get_did(): AbstractDID
    {
        $agent = $this->get_agent_connection_lazy();
        return $this->did ? $this->did : $agent->wallet->did;
    }

    public function get_anoncreds(): AbstractAnonCreds
    {
        $agent = $this->get_agent_connection_lazy();
        return $this->anonCreds ? $this->anonCreds : $agent->wallet->anoncreds;
    }

    public function get_cache()
    {
        $agent = $this->get_agent_connection_lazy();
        return $this->anonCreds ? $this->anonCreds : $agent->wallet->cache;
    }

    public static function init(
        string $server_uri, string $credentials, P2PConnection $p2p, int $io_timeout = null,
        AbstractImmutableCollection $storage = null,
        AbstractCrypto $crypto = null, AbstractMicroledgerList $microledgers = null,
        AbstractDID $did = null, AbstractPairwiseList $pairwise_storage = null
    )
    {
        $root = new Hub(
            $server_uri, $credentials, $p2p, $io_timeout, $storage, $crypto, $microledgers, $pairwise_storage, $did
        );
        $root->open();
        self::$ROOT_HUB = $root;
    }

    public static function context(
        string $server_uri, string $credentials, P2PConnection $p2p, int $io_timeout = null,
        AbstractImmutableCollection $storage = null,
        AbstractCrypto $crypto = null, AbstractMicroledgerList $microledgers = null,
        AbstractDID $did = null, AbstractPairwiseList $pairwise_storage = null
    ): Hub
    {
        $hub = new Hub(
            $server_uri, $credentials, $p2p, $io_timeout, $storage, $crypto, $microledgers, $pairwise_storage, $did
        );
        $old_hub = self::get_thread_local_hub();
        self::$THREAD_LOCAL_HUB = $hub;
        try {
            $hub->open();
            $old_hub_coro = self::$COROUTINE_LOCAL_HUB;
            $token = self::$COROUTINE_LOCAL_HUB = $hub;
            try {
                return $token;
            } finally {
                self::$COROUTINE_LOCAL_HUB = $token;
                $token->close();
                self::$COROUTINE_LOCAL_HUB = $old_hub_coro;
            }
        } finally {
            self::$THREAD_LOCAL_HUB = $old_hub;
        }
    }

    public static function get_root_hub()
    {
        return self::$ROOT_HUB;
    }

    public static function get_thread_local_hub()
    {
        return self::$THREAD_LOCAL_HUB;
    }

    public static function current_hub(): ?Hub
    {
        $inst = self::$COROUTINE_LOCAL_HUB;
        if (!$inst) {
            $root_hub = self::get_thread_local_hub() ? self::get_thread_local_hub() : self::get_root_hub();
            if (!$root_hub) {
                throw new SiriusInitializationError('Non initialized Sirius Agent connection');
            }
            $inst = $root_hub->copy();
            self::$COROUTINE_LOCAL_HUB = $inst;
        }
        return $inst;
    }

    public function __create_agent_instance()
    {
        $this->agent = new Agent(
            $this->server_uri,
            $this->credentials,
            $this->p2p,
            $this->timeout,
            $this->storage,
            null,
            SpawnStrategy::CONCURRENT
        );
    }
}