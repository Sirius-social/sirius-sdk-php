<?php

namespace Siruis\Agent\Wallet\Abstracts\Ledger;

use MyCLabs\Enum\Enum;
use RuntimeException;

class NYMRole extends Enum
{
    public const COMMON_USER = [null, 'null'];
    public const TRUSTEE = [0, 'TRUSTEE'];
    public const STEWARD = [2, 'STEWARD'];
    public const TRUST_ANCHOR = [101, 'TRUST_ANCHOR'];
    public const NETWORK_MONITOR = [201, 'NETWORK_MONITOR'];
    public const RESET = [null, ''];

    public function serialize()
    {
        return $this->getValue();
    }

    public static function deserialize(string $buffer)
    {
        $role_name = $buffer;
        if ($role_name == 'null') {
            return self::COMMON_USER;
        } elseif ($role_name == 'TRUSTEE') {
            return self::TRUSTEE;
        } elseif ($role_name == 'STEWARD') {
            return self::STEWARD;
        } elseif ($role_name == 'TRUST_ANCHOR') {
            return self::TRUST_ANCHOR;
        } elseif ($role_name == 'NETWORK_MONITOR') {
            return self::NETWORK_MONITOR;
        } elseif ($role_name == '') {
            return self::RESET;
        } else {
            throw new RuntimeException('Unexpected value ' . $buffer);
        }
    }
}
