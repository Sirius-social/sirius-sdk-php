<?php


namespace Siruis\Hub;


use Siruis\Agent\Ledgers\Ledger;
use Siruis\Agent\Listener\Listener;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Wallet\Impl\NonSecretsProxy;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Proxies\AnonCredsProxy;
use Siruis\Hub\Proxies\CacheProxy;
use Siruis\Hub\Proxies\CryptoProxy;
use Siruis\Hub\Proxies\DIDProxy;
use Siruis\Hub\Proxies\MicroledgersProxy;
use Siruis\Hub\Proxies\PairwiseProxy;
use Siruis\Messaging\Message;

class Init
{
    public static function DID()
    {
        return new DIDProxy();
    }

    public static function Crypto()
    {
        return new CryptoProxy();
    }

    public static function Microledgers()
    {
        return new MicroledgersProxy();
    }

    public static function PairwiseList()
    {
        return new PairwiseProxy();
    }

    public static function AnonCreds()
    {
        return new AnonCredsProxy();
    }

    public static function Cache()
    {
        return new CacheProxy();
    }

    public static function NonSecrets()
    {
        return new NonSecretsProxy();
    }


    public static function ledger(string $name): ?Ledger
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        return $agent->ledger($name);
    }

    public static function endpoints(): array
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        return $agent->endpoints;
    }

    public static function subscribe(): Listener
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        return $agent->subscribe();
    }

    public static function ping(): bool
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        return $agent->ping();
    }

    public static function send(
        Message $message, $their_vk, string $endpoint, ?string $my_vk, array $routing_keys
    )
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        $agent->send_message($message, $their_vk, $endpoint, $my_vk, $routing_keys);
    }

    public static function send_to(Message $message, Pairwise $to)
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        $agent->send_to($message, $to);
    }

    public static function generate_qr_code(string $value): string
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        return $agent->generate_qr_code($value);
    }

    public static function acquire(array $resources, float $lock_timeout, float $enter_timeout = null): array
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        if ($enter_timeout) {
            return $agent->acquire($resources, $lock_timeout, $enter_timeout);
        } else {
            return $agent->acquire($resources, $lock_timeout);
        }
    }

    public static function release()
    {
        $agent = Hub::current_hub()->get_agent_connection_lazy();
        $agent->release();
    }
}