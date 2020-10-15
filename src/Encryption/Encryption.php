<?php

namespace Siruis\Encryption;

include '../../Salt/autoload.php';

use Salt;
use SaltException;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Tuupola\Base58;

class Encryption
{
    public static function b64_to_bytes($value, $urlsafe = false)
    {
        if ($value instanceof string) {
            $value = mb_convert_encoding($value, 'ASCII');
        }
        if ($urlsafe) {
            // $missing_padding = strlen($value) % 4;
            // if ($missing_padding) {
            //     $value += b '='
            // }
            return static::urlsafe_b64decode($value);
        }
        return base64_decode($value);
    }

    public static function bytes_to_b64($value, $urlsafe = false)
    {
        if ($urlsafe) {
            $value = static::urlsafe_b64encode($value);
            return mb_convert_encoding($value, 'ASCII');
        }
        $value = base64_encode($value);
        return mb_convert_encoding($value, 'ASCII');
    }

    public static function b58_to_bytes($value)
    {
        $base58 = new Base58();
        return $base58->decode($value);
    }

    public static function bytes_to_b58($value)
    {
        $base58 = new Base58();
        return $base58->encode($value);
    }

    /**
     * @param bool $seed
     * @return array
     * @throws SiriusCryptoError
     * @throws SaltException
     */
    public static function create_keypair($seed = false)
    {
        if ($seed) {
            static::validate_seed($seed);
        } else {
            $seed = static::random_seed();
        }
        $salt = new Salt();
        return $salt->crypto_sign_keypair($seed);
    }

    private static function random_seed()
    {
        $key_size = Salt::secretbox_KEY;
        $salt = new Salt();
        return Salt::randombytes($key_size);
    }

    /**
     * @param $seed
     * @return bool|false|string
     * @throws SiriusCryptoError
     */
    private static function validate_seed($seed)
    {
        if (! $seed) {
            return false;
        }
        if (is_string($seed)) {
            if (strpos($seed, '=')) {
                $seed = static::b64_to_bytes($seed);
            } else {
                $seed = mb_convert_encoding($seed, 'ASCII');
            }
        }
        if (mb_strlen($seed) != 32) {
            throw new SiriusCryptoError('Seed value must be 32 bytes in length');
        }

        return $seed;
    }

    private static function urlsafe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
        return $data;
    }

    private static function urlsafe_b64decode($string)
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = mb_strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}
