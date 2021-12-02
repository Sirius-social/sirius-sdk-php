<?php


namespace Siruis\Agent\Wallet\Impl;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\AbstractNonSecrets;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;

class NonSecretsProxy extends AbstractNonSecrets
{
    /**
     * @var \Siruis\Agent\Connections\AgentRPC
     */
    private static $rpc;

    /**
     * NonSecretsProxy constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        self::$rpc = $rpc;
    }

    /**
     * @inheritDoc
     */
    public static function add_wallet_record(string $type_, string $id_, string $value, array $tags = null)
    {
        return self::$rpc->remoteCall(
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
    public static function update_wallet_record_value(string $type_, string $id_, string $value)
    {
        return self::$rpc->remoteCall(
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
    public static function update_wallet_record_tags(string $type_, string $id_, array $tags)
    {
        return self::$rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/update_wallet_record_tags',
            [
                'type_' => $type_,
                'id_' => $id_,
                'tags' => $tags
            ]
        );
    }

    public static function add_wallet_record_tags(string $type_, string $id_, array $tags)
    {
        return self::$rpc->remoteCall(
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
    public static function delete_wallet_record_tags(string $type_, string $id_, array $tag_names)
    {
        return self::$rpc->remoteCall(
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
    public static function delete_wallet_record(string $type_, string $id_)
    {
        return self::$rpc->remoteCall(
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
    public static function get_wallet_record(string $type_, string $id_, RetrieveRecordOptions $options): ?array
    {
        return self::$rpc->remoteCall(
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
    public static function wallet_search(string $type_, array $query, RetrieveRecordOptions $options, int $limit = 1): array
    {
        return self::$rpc->remoteCall(
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