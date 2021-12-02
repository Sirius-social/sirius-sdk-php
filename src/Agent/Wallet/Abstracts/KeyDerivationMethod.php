<?php

namespace Siruis\Agent\Wallet\Abstracts;

use MyCLabs\Enum\Enum;
use RuntimeException;

class KeyDerivationMethod extends Enum
{
    public const DEFAULT = 'ARGON2I_MOD';
    public const FAST = 'ARGON2I_INT';
    public const RAW = 'RAW';

    /**
     * Deserialize from the given buffer.
     *
     * @param string $buffer
     * @return string
     */
    public static function deserialize(string $buffer): string
    {
        if ($buffer === 'ARGON2I_MOD') {
            return self::DEFAULT;
        }

        if ($buffer === 'ARGON2I_INT') {
            return self::FAST;
        }

        if ($buffer === 'RAW') {
            return self::RAW;
        }

        throw new RuntimeException('Unexpected value ' . $buffer);
    }
}
