<?php


namespace Siruis\Agent\Microledgers;


class Microledgers
{
    public const METADATA_ATTR = 'txnMetadata';

    public static function serialize_ordering(array $value)
    {
        return json_encode($value);
    }
}