<?php

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

function DID(): DIDProxy
{
    return new DIDProxy();
}

function Crypto(): CryptoProxy
{
    return new CryptoProxy();
}

function Microledgers(): MicroledgersProxy
{
    return new MicroledgersProxy();
}

function PairwiseList(): PairwiseProxy
{
    return new PairwiseProxy();
}

function AnonCreds(): AnonCredsProxy
{
    return new AnonCredsProxy();
}

function Cache(): CacheProxy
{
    return new CacheProxy();
}

function NonSecrets(): NonSecretsProxy
{
    return new NonSecretsProxy();
}


/**
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 */
function ledger(string $name): ?Ledger
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    return $agent->ledger($name);
}

/**
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 */
function endpoints(): array
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    return $agent->endpoints;
}

/**
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 */
function subscribe(): Listener
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    return $agent->subscribe();
}

/**
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 */
function ping(): bool
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    return $agent->ping();
}

/**
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
 * @throws \GuzzleHttp\Exception\GuzzleException
 * @throws \Siruis\Errors\Exceptions\SiriusRPCError
 */
function send(
    Message $message, $their_vk, string $endpoint, ?string $my_vk, array $routing_keys
)
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    $agent->send_message($message, $their_vk, $endpoint, $my_vk, $routing_keys);
}

/**
 * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 * @throws \GuzzleHttp\Exception\GuzzleException
 * @throws \Siruis\Errors\Exceptions\SiriusRPCError
 */
function send_to(Message $message, Pairwise $to)
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    $agent->send_to($message, $to);
}

/**
 * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 */
function generate_qr_code(string $value): string
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    return $agent->generate_qr_code($value);
}

/**
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 */
function acquire(array $resources, float $lock_timeout, float $enter_timeout = null): array
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    if ($enter_timeout) {
        return $agent->acquire($resources, $lock_timeout, $enter_timeout);
    } else {
        return $agent->acquire($resources, $lock_timeout);
    }
}

/**
 * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
 */
function release()
{
    $agent = Hub::current_hub()->get_agent_connection_lazy();
    $agent->release();
}