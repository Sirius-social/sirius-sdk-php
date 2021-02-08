<?php


namespace Siruis\Hub\Core;


use Siruis\Agent\Microledgers\AbstractMicroledgerList;
use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Agent\Wallet\Abstracts\AbstractDID;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AbstractAnonCreds;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Storage\Abstracts\AbstractImmutableCollection;

class Hub
{
    public const ROOT_HUB = null;
    public const THREAD_LOCAL_HUB = null;
    public const COROUTINE_LOCAL_HUB = null;

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
    public $io_timeout;
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
        $this->io_timeout = $io_timeout;
        $this->storage = $storage;
        $this->crypto = $crypto;
        $this->microledgers = $microledgers;
        $this->pairwise_storage = $pairwise_storage;
        $this->did = $did;
        $this->anonCreds = $anonCreds;
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

    public static function get_root_hub()
    {
        return self::ROOT_HUB;
    }

    public static function get_thread_local_hub()
    {
        return self::THREAD_LOCAL_HUB;
    }

    public static function current_hub(): ?Hub
    {
        $inst = self::COROUTINE_LOCAL_HUB;
        if (!$inst) {
            $root_hub = self::get_thread_local_hub() or self::get_root_hub();
            if (!$root_hub) {
                throw new SiriusInitializationError('Non initialized Sirius Agent connection');
            }
            $inst = $root_hub->copy();
            self::COROUTINE_LOCAL_HUB[$inst] = $inst;
        }
        return $inst;
    }
}