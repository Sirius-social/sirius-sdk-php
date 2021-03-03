<?php


namespace Siruis\Agent\Wallet\Impl;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\AbstractNonSecrets;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;

class NonSecretsProxy extends AbstractNonSecrets
{
    /**
     * @var AgentRPC
     */
    private $rpc;

    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
    }

    /**
     * @inheritDoc
     */
    public function add_wallet_record(string $type_, string $id_, string $value, array $tags = null)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/add_wallet_record',
            [
                'type_' => $type_,
                'id_' => $id_,
                'value' => $value,
                'tags' => $tags
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function update_wallet_record_value(string $type_, string $id_, string $value)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/update_wallet_record_value',
            [
                'type_' => $type_,
                'id_' => $id_,
                'value' => $value
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function update_wallet_record_tags(string $type_, string $id_, array $tags)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/update_wallet_record_tags',
            [
                'type_' => $type_,
                'id_' => $id_,
                'tags' => $tags
            ]
        );
    }

    public function add_wallet_record_tags(string $type_, string $id_, array $tags)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/add_wallet_record_tags',
            [
                'type_' => $type_,
                'id_' => $id_,
                'tags' => $tags
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function delete_wallet_record_tags(string $type_, string $id_, array $tag_names)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/delete_wallet_record_tags',
            [
                'type_' => $type_,
                'id_' => $id_,
                'tag_names' => $tag_names
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function delete_wallet_record(string $type_, string $id_)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/delete_wallet_record',
            [
                'type_' => $type_,
                'id_' => $id_,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function get_wallet_record(string $type_, string $id_, RetrieveRecordOptions $options): ?array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_wallet_record',
            [
                'type_' => $type_,
                'id_' => $id_,
                'options' => $options
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function wallet_search(string $type_, array $query, RetrieveRecordOptions $options, int $limit = 1): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/wallet_search',
            [
                'type_' => $type_,
                'query' => $query,
                'options' => $options,
                'limit' => $limit
            ]
        );
    }
}