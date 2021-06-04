<?php

namespace Siruis\Agent\Wallet\Abstracts\Ledger;

use MyCLabs\Enum\Enum;
use RuntimeException;

class PoolAction extends Enum
{
    public const POOL_RESTART = 'POOL_RESTART';
    public const GET_VALIDATOR_INFO = 'GET_VALIDATOR_INFO';

    public function serialize()
    {
        return $this->getValue();
    }

    public static function deserialize(string $buffer)
    {
        if ($buffer == 'POOL_RESTART') {
            return self::POOL_RESTART;
        } elseif ($buffer == 'GET_VALIDATOR_INFO') {
            return self::GET_VALIDATOR_INFO;
        } else {
            throw new RuntimeException('Unexpected value ' . $buffer);
        }
    }
}
