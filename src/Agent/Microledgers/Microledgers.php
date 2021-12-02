<?php


namespace Siruis\Agent\Microledgers;


class Microledgers
{
    public const METADATA_ATTR = 'txnMetadata';
    public const ATTR_TIME = 'txnTime';

    /**
     * @param array $value
     * @return false|string
     * @throws \JsonException
     */
    public static function serialize_ordering(array $value)
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}