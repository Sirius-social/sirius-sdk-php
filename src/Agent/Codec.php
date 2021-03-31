<?php


namespace Siruis\Agent;


class Codec
{
    const ENCODE_PREFIX = [
        'string' => 1,
        'bool' => 2,
        'int' => 3,
        'float' => 4,
        'null' => 9
    ];

    const DECODE_PREFIX = [
        'bool' => 2,
        'int' => 3,
        'float' => 4,
        'null' => 9
    ];

    const I32_BOUND = 2**31;

    public static function encode($raw = null): string
    {
        if (!$raw) {
            return (string) self::I32_BOUND;
        }
        $stringified = (string)$raw;
        if (is_bool($raw)) {
            return self::ENCODE_PREFIX['bool'].$raw ? self::I32_BOUND + 2 : self::I32_BOUND + 1;
        }
        if (is_int($raw) && -self::I32_BOUND < $raw && $raw < self::I32_BOUND) {
            return $stringified;
        }
        return self::ENCODE_PREFIX[(string)gettype($raw)].(string)(bin2hex($stringified) . self::I32_BOUND);
    }

    /**
     * @param string $value
     * @return string|null|bool|int|float
     */
    public static function decode(string $value)
    {
        if (-self::I32_BOUND < (int)$value && (int)$value < self::I32_BOUND) {
            return (int)$value;
        } elseif ((int)$value == self::I32_BOUND) {
            return null;
        }
        $prefix = (int)$value[0];
        $value = (int)substr($value, 1);
        $ival = (int)$value - self::I32_BOUND;
        if ($ival == 0) {
            return '';
        } elseif ($ival == 1) {
            return false;
        } elseif ($ival == 2) {
            return true;
        }
        $blen = ceil(log($ival, 16)/2);
        $ibytes = hex2bin($ival);
        return self::DECODE_PREFIX[$prefix].$ibytes;
    }

    public static function cred_attr_value($raw = null): array
    {
        return [
            'raw' => !$raw ? '' : (string)$raw,
            'encoded' => static::encode($raw)
        ];
    }

    public static function canon(string $raw_attr_name): string
    {
        if ($raw_attr_name) {
            return strtolower(str_replace($raw_attr_name, '', ' '));
        }
        return $raw_attr_name;
    }
}