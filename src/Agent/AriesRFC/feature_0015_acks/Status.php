<?php


namespace Siruis\Agent\AriesRFC\feature_0015_acks;


use MyCLabs\Enum\Enum;

class Status extends Enum
{
    public const OK = 'OK';

    public const PENDING = 'PENDING';

    public const FAIL = 'FAIL';
}