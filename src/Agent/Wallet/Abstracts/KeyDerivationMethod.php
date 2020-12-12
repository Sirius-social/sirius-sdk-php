<?php

namespace Siruis\Agent\Wallet\Abstracts;

use MyCLabs\Enum\Enum;
use RuntimeException;

class KeyDerivationMethod extends Enum
{
    public const DEFAULT = 'ARGON2I_MOD';
    public const FAST = 'ARGON2I_INT';
    public const RAW = 'RAW';

    public function serialize()
    {
        return $this->getValue();
    }

    public function deserialize(string $buffer)
    {
        if ($buffer == 'ARGON2I_MOD') {
            return self::DEFAULT;
        } elseif ($buffer == 'ARGON2I_INT') {
            return self::FAST;
        } elseif ($buffer == 'RAW') {
            return self::RAW;
        } else {
            throw new RuntimeException('Unexpected value ' . $buffer);
        }
    }
}
