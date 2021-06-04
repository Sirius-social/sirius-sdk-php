<?php

namespace Siruis\Agent\Wallet;

use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Impl\AnonCredsProxy;
use Siruis\Agent\Wallet\Impl\CacheProxy;
use Siruis\Agent\Wallet\Impl\CryptoProxy;
use Siruis\Agent\Wallet\Impl\DIDProxy;
use Siruis\Agent\Wallet\Impl\LedgerProxy;
use Siruis\Agent\Wallet\Impl\NonSecretsProxy;
use Siruis\Agent\Wallet\Impl\PairwiseProxy;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;

class DynamicWallet
{
    /**
     * @var AgentRPC
     */
    public $rpc;

    /**
     * @var DIDProxy
     */
    public $did;

    /**
     * @var CryptoProxy
     */
    public $crypto;

    /**
     * @var CacheProxy
     */
    public $cache;

    /**
     * @var PairwiseProxy
     */
    public $pairwise;

    /**
     * @var NonSecretsProxy
     */
    public $non_secrets;

    /**
     * @var LedgerProxy
     */
    public $ledger;

    /**
     * @var AnonCredsProxy
     */
    public $anoncreds;

    /**
     * DynamicWallet constructor.
     * @param AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
        $this->did = new DIDProxy($rpc);
        $this->crypto = new CryptoProxy($rpc);
        $this->cache = new CacheProxy($rpc);
        $this->pairwise = new PairwiseProxy($rpc);
        $this->ledger = new LedgerProxy($rpc);
        $this->anoncreds = new AnonCredsProxy($rpc);
        $this->non_secrets = new NonSecretsProxy($rpc);
    }

    /**
     * Generate wallet key.
     *
     * @param string|null $seed
     * @return string
     * @throws SiriusConnectionClosed
     */
    public function generate_wallet_key(string $seed = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/generate_wallet_key',
            [
                'seed' => $seed
            ]
        );
    }
}
