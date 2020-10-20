<?php

namespace Siruis\Encryption;

include '../../Salt/autoload.php';

use ParagonIE_Sodium_Compat;
use phpDocumentor\Reflection\Types\String_;
use Salt;
use SaltException;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use SodiumException;
use Tuupola\Base58;

class Encryption
{
    /**
     * @param $value
     * @param bool $urlsafe
     * @return string
     * @throws SodiumException
     */
    public static function b64_to_bytes($value, $urlsafe = false)
    {
        if (is_string($value)) {
            $value = mb_convert_encoding($value, 'ASCII');
        }
        if ($urlsafe) {
            // $missing_padding = strlen($value) % 4;
            // if ($missing_padding) {
            //     $value += b '='
            // }
            return ParagonIE_Sodium_Compat::base642bin($value, ParagonIE_Sodium_Compat::BASE64_VARIANT_URLSAFE);
        }
        return ParagonIE_Sodium_Compat::base642bin($value, ParagonIE_Sodium_Compat::BASE64_VARIANT_ORIGINAL);
    }

    /**
     * @param $value
     * @param bool $urlsafe
     * @return string
     * @throws SodiumException
     */
    public static function bytes_to_b64($value, $urlsafe = false)
    {
        if ($urlsafe) {
            $value = ParagonIE_Sodium_Compat::bin2base64($value, ParagonIE_Sodium_Compat::BASE64_VARIANT_URLSAFE);
            return mb_convert_encoding($value, 'ASCII');
        }
        $value = ParagonIE_Sodium_Compat::bin2base64($value, ParagonIE_Sodium_Compat::BASE64_VARIANT_ORIGINAL);
        return mb_convert_encoding($value, 'ASCII');
    }

    /**
     * @param $value
     * @return string
     */
    public static function b58_to_bytes($value)
    {
        $base58 = new Base58();
        return $base58->decode($value);
    }

    /**
     * @param $value
     * @return string
     */
    public static function bytes_to_b58($value)
    {
        $base58 = new Base58();
        return $base58->encode($value);
    }

    /**
     * @param bool $seed
     * @return array
     * @throws SiriusCryptoError
     * @throws SodiumException
     */
    public static function create_keypair($seed = false)
    {
        if ($seed) {
            static::validate_seed($seed);
        } else {
            $seed = static::random_seed();
        }
        $keys = ParagonIE_Sodium_Compat::crypto_sign_seed_keypair($seed);

        return [
            'verkey' => ParagonIE_Sodium_Compat::crypto_sign_publickey($keys),
            'sigkey' => ParagonIE_Sodium_Compat::crypto_sign_secretkey($keys)
        ];
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
     * @throws SiriusCryptoError|SodiumException
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
}
