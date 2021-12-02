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
     * @var \Siruis\Agent\Connections\AgentRPC
     */
    public $rpc;

    /**
     * @var \Siruis\Agent\Wallet\Impl\DIDProxy
     */
    public $did;

    /**
     * @var \Siruis\Agent\Wallet\Impl\CryptoProxy
     */
    public $crypto;

    /**
     * @var \Siruis\Agent\Wallet\Impl\CacheProxy
     */
    public $cache;

    /**
     * @var \Siruis\Agent\Wallet\Impl\PairwiseProxy
     */
    public $pairwise;

    /**
     * @var \Siruis\Agent\Wallet\Impl\NonSecretsProxy
     */
    public $non_secrets;

    /**
     * @var \Siruis\Agent\Wallet\Impl\LedgerProxy
     */
    public $ledger;

    /**
     * @var \Siruis\Agent\Wallet\Impl\AnonCredsProxy
     */
    public $anoncreds;

    /**
     * DynamicWallet constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
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
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
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
