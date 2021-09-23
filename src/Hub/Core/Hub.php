<?php


namespace Siruis\Hub\Core;


use RuntimeException;
use Siruis\Agent\Agent\Agent;
use Siruis\Agent\Agent\SpawnStrategy;
use Siruis\Agent\Connections\BaseAgentConnection;
use Siruis\Agent\Microledgers\AbstractMicroledgerList;
use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Agent\Wallet\Abstracts\AbstractCache;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Agent\Wallet\Abstracts\AbstractDID;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AbstractAnonCreds;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\AbstractNonSecrets;
use Siruis\Agent\Wallet\Impl\NonSecretsProxy;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Helpers\ArrayHelper;
use Siruis\Hub\Context;
use Siruis\Storage\Abstracts\AbstractImmutableCollection;

class Hub
{
    public static $ROOT_HUB = [];
    public static $THREAD_LOCAL_HUB = [];

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
    /**
     * @var Context
     */
    public static $context;
    /**
     * @var bool
     */
    protected $allocate_agent;
    /**
     * @var AbstractNonSecrets|null
     */
    protected $non_secrets;
    /**
     * @var AbstractCache|null
     */
    protected $cache;

    public function __construct(
        string $server_uri, string $credentials, P2PConnection $p2p, int $io_timeout = null,
        AbstractImmutableCollection $storage = null, AbstractCrypto $crypto = null,
        AbstractMicroledgerList $microledgers = null, AbstractPairwiseList $pairwise_storage = null,
        AbstractDID $did = null, AbstractAnonCreds $anonCreds = null, $context = null,
        AbstractNonSecrets $non_secrets = null, AbstractCache $cache = null
    )
    {
        if ($server_uri || $credentials || $p2p) {
            if ($server_uri && $credentials && $p2p) {
                $this->allocate_agent = true;
            } else {
                throw new RuntimeException('You must specify server_uri, credentials, p2p');
            }
        } else {
            $this->allocate_agent = false;
        }
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
        $this->non_secrets = $non_secrets;
        $this->cache = $cache;
        $this->__create_agent_instance();
        self::$context = $context ?? Context::getInstance();
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
        $inst->anonCreds = $this->anonCreds;
        $inst->non_secrets = $this->non_secrets;
        $inst->storage = $this->storage;
        $inst->cache = $this->cache;
        return $inst;
    }

    public function abort()
    {
        if (!$this->allocate_agent) {
            return;
        }
        $old_agent = $this->agent;
        $this->__create_agent_instance();
        if ($old_agent->isOpen()) {
            $old_agent->close();
        }
    }

    public function get_agent_connection_lazy(): Agent
    {
        if (!$this->agent->isOpen()) {
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
        if ($this->agent->isOpen()) {
            $this->agent->close();
        }
    }

    public function get_crypto(): AbstractCrypto
    {
        if ($this->allocate_agent) {
            $agent = $this->get_agent_connection_lazy();
            return $this->crypto ? $this->crypto : $agent->wallet->crypto;
        } else {
            return $this->crypto;
        }
    }

    public function get_microledgers(): AbstractMicroledgerList
    {
        if ($this->allocate_agent) {
            $agent = $this->get_agent_connection_lazy();
            return $this->microledgers ? $this->microledgers : $agent->microledgers;
        } else {
            return $this->microledgers;
        }
    }

    public function get_pairwise_list(): AbstractPairwiseList
    {
        if ($this->allocate_agent) {
            $agent = $this->get_agent_connection_lazy();
            return $this->pairwise_storage ? $this->pairwise_storage : $agent->pairwise_list;
        } else {
            return $this->pairwise_storage;
        }
    }

    public function get_did(): AbstractDID
    {
        if ($this->allocate_agent) {
            $agent = $this->get_agent_connection_lazy();
            return $this->did ? $this->did : $agent->wallet->did;
        } else {
            return $this->did;
        }
    }

    public function get_anoncreds(): AbstractAnonCreds
    {
        if ($this->allocate_agent) {
            $agent = $this->get_agent_connection_lazy();
            return $this->anonCreds ? $this->anonCreds : $agent->wallet->anoncreds;
        } else {
            return $this->anonCreds;
        }
    }

    public function get_cache()
    {
        if ($this->allocate_agent) {
            $agent = $this->get_agent_connection_lazy();
            return $this->cache ? $this->cache : $agent->wallet->cache;
        } else {
            return $this->cache;
        }
    }

    /**
     * @return AbstractNonSecrets|NonSecretsProxy|null
     */
    public function get_non_secrets()
    {
        if ($this->allocate_agent) {
            $agent = $this->get_agent_connection_lazy();
            return $this->non_secrets ?? $agent->wallet->non_secrets;
        } else {
            return $this->non_secrets;
        }
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

    public static function alloc_context(
        string $server_uri, string $credentials, P2PConnection $p2p, int $io_timeout = null,
        AbstractImmutableCollection $storage = null,
        AbstractCrypto $crypto = null, AbstractMicroledgerList $microledgers = null,
        AbstractDID $did = null, AbstractPairwiseList $pairwise_storage = null
    )
    {
        $hub = new Hub(
            $server_uri, $credentials, $p2p, $io_timeout, $storage, $crypto, $microledgers, $pairwise_storage, $did
        );
        $old_hub = self::get_thread_local_hub();
        self::$THREAD_LOCAL_HUB['hub'] = $hub;
        $hub->open();
        self::$context->set('old_hub', $old_hub);
        self::$context->set('hub', $hub);
    }

    public static function free_context()
    {
        $old_hub = self::$context->get('old_hub');
        self::$context->clear();
        self::current_hub()->close();
        self::$context->set('hub', $old_hub);
    }

    public static function get_root_hub()
    {
        return self::$ROOT_HUB;
    }

    public static function get_thread_local_hub()
    {
        return ArrayHelper::getValueWithKeyFromArray('hub', self::$THREAD_LOCAL_HUB);
    }

    public static function current_hub(): ?Hub
    {
        $inst = self::$context->get('hub');
        if (!$inst) {
            $root_hub = self::get_thread_local_hub() ? self::get_thread_local_hub() : self::get_root_hub();
            if (!$root_hub) {
                throw new SiriusInitializationError('Non initialized Sirius Agent connection');
            }
            $inst = $root_hub->copy();
            self::$context->set('hub', $inst);
        }
        return $inst;
    }

    public function __create_agent_instance()
    {
        if ($this->allocate_agent) {
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
}