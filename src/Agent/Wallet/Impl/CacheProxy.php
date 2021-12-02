<?php


namespace Siruis\Agent\Wallet\Impl;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\AbstractCache;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Agent\Wallet\Abstracts\PurgeOptions;

class CacheProxy extends AbstractCache
{
    /**
     * @var \Siruis\Agent\Connections\AgentRPC
     */
    private $rpc;

    /**
     * CacheProxy constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
    }


    /**
     * @inheritDoc
     */
    public function get_schema(string $pool_name, string $submitter_did, string $id, CacheOptions $options): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_schema',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'id_' => $id,
                'options' => $options
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function get_cred_def(string $pool_name, string $submitter_did, string $id, CacheOptions $options): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_cred_def',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'id_' => $id,
                'options' => $options
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function purge_schema_cache(PurgeOptions $options)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/purge_schema_cache',
            [
                'options' => $options
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function purge_cred_def_cache(PurgeOptions $options)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/purge_cred_def_cache',
            [
                'options' => $options
            ]
        );
    }
}