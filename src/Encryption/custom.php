<?php

use Siruis\Errors\Exceptions\SiriusCryptoError;
use StephenHill\Base58;

/**
 * Convert a base64 string to bytes.
 *
 * @param $value
 * @param bool $urlsafe
 *
 * @return string
 *
 * @throws \SodiumException
 */
function b64_to_bytes($value, bool $urlsafe = false): string
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
 * Convert a byte string to base 64.
 *
 * @param string $value
 * @param bool $urlsafe
 *
 * @return string
 *
 * @throws \SodiumException
 */
function bytes_to_b64(string $value, bool $urlsafe = false): string
{
    if ($urlsafe) {
        return mb_convert_encoding(sodium_bin2base64($value, SODIUM_BASE64_VARIANT_URLSAFE), 'ASCII');
    }

    return mb_convert_encoding(sodium_bin2base64($value, SODIUM_BASE64_VARIANT_ORIGINAL), 'ASCII');
}

/**
 * Convert a base 58 string to bytes.
 *
 * Small cache provided for key conversions which happen frequently in pack
 * and unpack and message handling.
 *
 * @param string $value
 *
 * @return string
 */
function b58_to_bytes(string $value): string
{
    return (new Base58())->decode($value);
}

/**
 * Convert a byte string to base 58.
 *
 * Small cache provided for key conversions which happen frequently in pack
 * and unpack and message handling.
 *
 * @param string $value
 *
 * @return string
 */
function bytes_to_b58(string $value): string
{
    return mb_convert_encoding((new Base58)->encode($value), 'ASCII');
}

/**
 * Create a public and private signing keypair from a seed value.
 *
 * @param string|null $seed
 *
 * @return array
 *
 * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
 * @throws \SodiumException
 */
function create_keypair(string $seed = null): array
{
    if ($seed) {
        validate_seed($seed);
    } else {
        $seed = random_seed();
    }

    $keys = sodium_crypto_sign_seed_keypair($seed);

    return [sodium_crypto_sign_publickey($keys), sodium_crypto_sign_secretkey($keys)];
}

/**
 * Generate a random seed value.
 *
 * @return string
 *
 * @throws \Exception
 */
function random_seed(): string
{
    return random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
}

/**
 * Convert a seed parameter to standard format and check length.
 *
 * @param $seed
 *
 * @return string|null
 *
 * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
 * @throws \SodiumException
 */
function validate_seed($seed): ?string
{
    if (!$seed) {
        return null;
    }

    if (is_string($seed)) {
        if (strpos($seed, '=')) {
            $seed = b64_to_bytes($seed);
        } else {
            $seed = mb_convert_encoding($seed, 'ASCII');
        }
    }

    if (mb_strlen($seed) !== 32) {
        throw new SiriusCryptoError('Seed value must be 32 bytes in length');
    }

    return $seed;
}

/**
 * Sign a message using a private signing key.
 *
 * @param string $message The message to sign
 * @param string $secret The private signing key
 *
 * @return false|string The signature
 *
 * @throws \SodiumException
 */
function sign_message(string $message, string $secret)
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
 *
 * @return bool
 * @throws \SodiumException
 */
function verify_signed_message(string $verkey, string $message, string $signature): bool
{
    $signed = $signature . $message;

    return (bool) sodium_crypto_sign_open($signed, $verkey);
}

function did_from_verkey(string $verkey)
{
    return substr($verkey, 0, 16);
}
