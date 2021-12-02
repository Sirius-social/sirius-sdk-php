<?php

namespace Siruis\Agent\Wallet\Abstracts\Ledger;

use MyCLabs\Enum\Enum;
use RuntimeException;

class PoolAction extends Enum
{
    public const POOL_RESTART = 'POOL_RESTART';
    public const GET_VALIDATOR_INFO = 'GET_VALIDATOR_INFO';

    /**
     * Deserialize from the given buffer.
     *
     * @param string $buffer
     * @return string
     */
    public static function deserialize(string $buffer): string
    {
        if ($buffer === 'POOL_RESTART') {
            return self::POOL_RESTART;
        }

        if ($buffer === 'GET_VALIDATOR_INFO') {
            return self::GET_VALIDATOR_INFO;
        }

        throw new RuntimeException('Unexpected value ' . $buffer);
    }
}
