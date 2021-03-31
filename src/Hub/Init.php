<?php


namespace Siruis\Hub;


use GuzzleHttp\Exception\GuzzleException;
use Siruis\Agent\Agent\Agent;
use Siruis\Agent\Ledgers\Ledger;
use Siruis\Agent\Listener\Listener;
use Siruis\Agent\Microledgers\AbstractMicroledgerList;
use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Wallet\Abstracts\AbstractCache;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Agent\Wallet\Abstracts\AbstractDID;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Errors\Exceptions\SiriusRPCError;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Proxies\AnonCredsProxy;
use Siruis\Hub\Proxies\CacheProxy;
use Siruis\Hub\Proxies\DIDProxy;
use Siruis\Hub\Proxies\MicroledgersProxy;
use Siruis\Hub\Proxies\PairwiseProxy;
use Siruis\Messaging\Message;

class Init
{
    /**
     * @var Agent
     */
    static $agent;
    /**
     * @var AbstractPairwiseList
     */
    static $PairwiseList;
    /**
     * @var AbstractDID
     */
    static $DID;
    /**
     * @var AbstractCrypto
     */
    static $Crypto;
    /**
     * @var AbstractMicroledgerList
     */
    static $Microledgers;
    /**
     * @var AnonCredsProxy
     */
    static $AnonCreds;
    /**
     * @var AbstractCache
     */
    static $Cache;

    /**
     * Init constructor.
     * @throws SiriusInitializationError
     */
    public function __construct()
    {
        self::$agent = Hub::current_hub()->get_agent_connection_lazy();
        self::$PairwiseList = new PairwiseProxy();
        self::$DID = new DIDProxy();
        self::$Microledgers = new MicroledgersProxy();
        self::$AnonCreds = new AnonCredsProxy();
        self::$Cache = new CacheProxy();
    }

    public static function ledger(string $name): ?Ledger
    {
        return self::$agent->ledger($name);
    }

    public static function endpoints(): array
    {
        return self::$agent->endpoints;
    }

    public static function subscribe(): Listener
    {
        return self::$agent->subscribe();
    }

    public static function ping(): bool
    {
        return self::$agent->ping();
    }

    /**
     * @param Message $message
     * @param $their_vk
     * @param string $endpoint
     * @param string|null $my_vk
     * @param array $routing_keys
     * @throws GuzzleException
     * @throws SiriusConnectionClosed
     * @throws SiriusRPCError
     */
    public static function send(
        Message $message, $their_vk, string $endpoint, ?string $my_vk, array $routing_keys
    )
    {
        self::$agent->send_message($message, $their_vk, $endpoint, $my_vk, $routing_keys);
    }

    /**
     * @param Message $message
     * @param Pairwise $to
     * @throws GuzzleException
     * @throws SiriusConnectionClosed
     * @throws SiriusRPCError
     */
    public static function send_to(Message $message, Pairwise $to)
    {
        self::$agent->send_to($message, $to);
    }

    public static function generate_qr_code(string $value): string
    {
        return self::$agent->generate_qr_code($value);
    }

    /**
     * @param array $resources
     * @param float $lock_timeout
     * @param float|null $enter_timeout
     * @return array
     * @throws SiriusInitializationError
     */
    public static function acquire(array $resources, float $lock_timeout, float $enter_timeout = null): array
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        if ($enter_timeout) {
            return $agent->acquire($resources, $lock_timeout, $enter_timeout);
        } else {
            return $agent->acquire($resources, $lock_timeout);
        }
    }

    /**
     * @throws SiriusInitializationError
     */
    public static function release()
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        $agent->release();
    }
}