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

    /**
     * Specify data which should be serialized to JSON. This method returns data that can be serialized by json_encode()
     * natively.
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        [$_, $role_name] = $this->getValue();
        return $role_name;
    }

    /**
     * Deserialize from the given buffer.
     *
     * @param string $buffer
     * @return array
     */
    public static function deserialize(string $buffer): array
    {
        $role_name = $buffer;
        if ($role_name === 'null') {
            return self::COMMON_USER;
        }

        if ($role_name === 'TRUSTEE') {
            return self::TRUSTEE;
        }

        if ($role_name === 'STEWARD') {
            return self::STEWARD;
        }

        if ($role_name === 'TRUST_ANCHOR') {
            return self::TRUST_ANCHOR;
        }

        if ($role_name === 'NETWORK_MONITOR') {
            return self::NETWORK_MONITOR;
        }

        if ($role_name === '') {
            return self::RESET;
        }

        throw new RuntimeException('Unexpected value ' . $buffer);
    }
}
