<?php

namespace Siruis\Encryption;

use Siruis\Errors\Exceptions\SiriusCryptoError;
use StephenHill\Base58;

class Encryption
{
    /**
     * @param $value
     * @param bool $urlsafe
     * @return string
     * @throws \SodiumException
     */
    public static function b64_to_bytes($value, bool $urlsafe = false): string
    {
        if (is_string($value)) {
            $value = mb_convert_encoding($value, 'ASCII');
        }
        if ($urlsafe) {
             $missing_padding = strlen($value) % 4;
             if ($missing_padding) {
                 $value .= str_repeat(b'=', (4 - $missing_padding));
             }

            return sodium_base642bin($value, SODIUM_BASE64_VARIANT_URLSAFE);
        }
        return sodium_base642bin($value, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * @param $value
     * @param bool $urlsafe
     * @return array|false|string
     * @throws \SodiumException
     */
    public static function bytes_to_b64($value, bool $urlsafe = false)
    {
        if ($urlsafe) {
            $value = sodium_bin2base64($value, SODIUM_BASE64_VARIANT_URLSAFE);
            return mb_convert_encoding($value, 'ASCII');
        }
        $value = sodium_bin2base64($value, SODIUM_BASE64_VARIANT_ORIGINAL);
        return mb_convert_encoding($value, 'ASCII');
    }

    /**
     * @param $value
     * @return string
     */
    public static function b58_to_bytes($value): string
    {
        return (new Base58())->decode($value);
    }

    /**
     * @param $value
     * @return string
     */
    public static function bytes_to_b58($value): string
    {
        $base58 = new Base58();
        return mb_convert_encoding($base58->encode($value), 'ascii');
    }

    /**
     * @param mixed $seed
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     * @throws \Exception
     */
    public static function create_keypair($seed = false): array
    {
        if ($seed) {
            static::validate_seed($seed);
        } else {
            $seed = static::random_seed();
        }
        $keys = sodium_crypto_sign_seed_keypair($seed);

        return [
            'verkey' => sodium_crypto_sign_publickey($keys),
            'sigkey' => sodium_crypto_sign_secretkey($keys)
        ];
    }

    /**
     * @return string
     * @throws \Exception
     */
    private static function random_seed(): string
    {
        return random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    /**
     * @param mixed $seed
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     */
    private static function validate_seed($seed): void
    {
        if (! $seed) {
            return;
        }
        if (is_string($seed)) {
            if (strpos($seed, '=')) {
                $seed = static::b64_to_bytes($seed);
            } else {
                $seed = mb_convert_encoding($seed, 'ASCII');
            }
        }
        if (mb_strlen($seed) !== 32) {
            throw new SiriusCryptoError('Seed value must be 32 bytes in length');
        }

    }

    /**
     * Sign a message using a private signing key.
     *
     * @param string $message The message to sign
     * @param string $secret The private signing key
     * @return false|string The signature
     * @throws \SodiumException
     */
    public static function sign_message(string $message, string $secret)
    {
        $result = sodium_crypto_sign($message, $secret);
        return substr($result, 0, SODIUM_CRYPTO_SIGN_BYTES);
    }

    /**
     * Verify a signed message according to a public verification key.
     *
     * @param string $verkey The verkey to use in verification
     * @param string $message original message
     * @param string $signature The signed message
     * @return bool
     * @throws \SodiumException
     */
    public static function verify_signed_message(string $verkey, string $message, string $signature): bool
    {
        $signed = $signature . $message;
        return (bool)sodium_crypto_sign_open($signed, $verkey);
    }

    /**
     * @param string $verkey
     * @return false|string
     */
    public static function did_from_verkey(string $verkey)
    {
        return substr($verkey, 0, 16);
    }
}
