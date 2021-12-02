<?php


namespace Siruis\Agent\Wallet\Impl;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\AbstractDID;

class DIDProxy extends AbstractDID
{
    /**
     * @var \Siruis\Agent\Connections\AgentRPC
     */
    private $rpc;

    /**
     * DIDProxy constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
    }

    /**
     * @inheritDoc
     */
    public function create_and_store_my_did(string $did = null, string $seed = null, bool $cid = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/create_and_store_my_did',
            [
                'did' => $did,
                'seed' => $seed,
                'cid' => $cid
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function store_their_did(string $did, string $verkey = null)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/store_their_did',
            [
                'did' => $did,
                'verkey' => $verkey
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function set_did_metadata(string $did, array $metadata = null)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/set_did_metadata',
            [
                'did' => $did,
                'metadata' => $metadata
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function list_my_dids_with_meta(): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/list_my_dids_with_meta'
        );
    }

    /**
     * @inheritDoc
     */
    public function get_did_metadata($did): ?array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_did_metadata',
            [
                'did' => $did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function key_for_local_did(string $did): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/key_for_local_did',
            [
                'did' => $did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function key_for_did(string $pool_name, string $did): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/key_for_did',
            [
                'pool_name' => $pool_name,
                'did' => $did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function create_key(string $seed = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/create_key__did',
            [
                'seed' => $seed
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function replce_keys_start(string $did, string $seed = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/replace_keys_start',
            [
                'did' => $did,
                'seed' => $seed
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function replace_keys_apply(string $did)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/replace_keys_apply',
            [
                'did' => $did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function set_key_metadata(string $verkey, array $metadata)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/set_key_metadata__did',
            [
                'verkey' => $verkey,
                'metadata' => $metadata
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function get_key_metadata(string $verkey): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_key_metadata__did',
            [
                'verkey' => $verkey,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function set_endpoint_for_did(string $did, string $address, string $transport_key)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/set_endpoint_for_did',
            [
                'did' => $did,
                'address' => $address,
                'transport_key' => $transport_key
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function get_endpoint_for_did(string $pool_name, string $did)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_endpoint_for_did',
            [
                'pool_name' => $pool_name,
                'did' => $did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function get_my_did_with_meta(string $did)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_my_did_with_meta',
            [
                'did' => $did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function abbreviate_verkey(string $did, string $full_verkey): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/abbreviate_verkey',
            [
                'did' => $did,
                'full_verkey' => $full_verkey
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function qualify_did(string $did, string $method): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/qualify_did',
            [
                'did' => $did,
                'method' => $method
            ]
        );
    }
}