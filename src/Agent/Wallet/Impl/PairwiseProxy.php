<?php


namespace Siruis\Agent\Wallet\Impl;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\AbstractPairwise;

class PairwiseProxy extends AbstractPairwise
{
    /**
     * @var \Siruis\Agent\Connections\AgentRPC
     */
    private $rpc;

    /**
     * PairwiseProxy constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
    }

    /**
     * @inheritDoc
     */
    public function is_pairwise_exists(string $their_did): bool
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/is_pairwise_exists',
            [
                'their_did' => $their_did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function create_pairwise(string $their_did, string $my_did, array $metadata = null, array $tags = null)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/create_pairwise',
            [
                'their_did' => $their_did,
                'my_did' => $my_did,
                'metadata' => $metadata,
                'tags' => $tags
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function list_pairwise(): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/list_pairwise'
        );
    }

    /**
     * @inheritDoc
     */
    public function get_pairwise(string $their_did): ?array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_pairwise',
            [
                'their_did' => $their_did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function set_pairwise_metadata(string $their_did, array $metadata = null, array $tags = null)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/set_pairwise_metadata',
            [
                'their_did' => $their_did,
                'metadata' => $metadata,
                'tags' => $tags
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function search(array $tags, int $limit = null)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/search_pairwise',
            [
                'tags' => $tags,
                'limit' => $limit
            ]
        );
    }
}