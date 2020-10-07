<?php

namespace Siruis\Encryption;

include '../../Salt/autoload.php';

use Exception;
use http\Exception\UnexpectedValueException;
use SaltException;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\Encryption\Encryption;
use Salt;

class Ed25519
{
    /**
     * @param $b58_or_bytes
     * @return string
     */
    public static function ensure_is_bytes($b58_or_bytes)
    {
        if (is_string($b58_or_bytes)) {
            return Encryption::b58_to_bytes($b58_or_bytes);
        }

        return $b58_or_bytes;
    }

    /**
     * Assemble the recipients block of a packed message.
     *
     * @param $to_verkeys
     * @param null $from_verkey
     * @param null $from_sigkey
     * @return array
     * @throws SiriusCryptoError|SaltException
     */
    public static function prepare_pack_recipient_keys($to_verkeys, $from_verkey = null, $from_sigkey = null)
    {
        if ($from_verkey && !$from_sigkey || $from_sigkey && !$from_verkey) {
            throw new SiriusCryptoError('Both verkey and sigkey needed to authenticated encrypt message');
        }

        $cek = sodium_crypto_secretstream_xchacha20poly1305_keygen();
        $recips = [];

        foreach ($to_verkeys as $target_vk) {
            $target_pk = sodium_crypto_sign_ed25519_pk_to_curve25519($target_vk);

            if ($from_verkey) {
                $b58_from_verkey = Encryption::bytes_to_b58($from_verkey);
                $sender_vk = mb_convert_encoding($b58_from_verkey, 'ASCII');
                $enc_sender = sodium_crypto_box_seal($sender_vk, $target_pk);
                $sk = sodium_crypto_sign_ed25519_pk_to_curve25519($from_sigkey);
                $nonce = Salt::randombytes(Salt::secretbox_NONCE);
                $enc_cek = Salt::secretbox($cek, $nonce, [$target_pk, $sk]);
            } else {
                $enc_sender = null;
                $nonce = null;
                $enc_cek = sodium_crypto_box_seal($cek, $target_pk);
            }

            $ar_sender = '';
            if ($enc_sender) {
                $ar_sender = Encryption::bytes_to_b64($enc_sender, true);
            } else {
                $ar_sender = null;
            }
            $iv = '';
            if ($nonce) {
                $iv = Encryption::bytes_to_b64($nonce, true);
            } else {
                $iv = null;
            }

            array_push($recips, [
                'encrypted_key' => Encryption::bytes_to_b64($enc_cek, true),
                'header' => [
                    'kid' => Encryption::bytes_to_b58($target_vk),
                    'sender' => $ar_sender,
                    'iv' => $iv
                ]
            ]);
        }

        $authK = '';
        if ($from_verkey) {

        }

        $data = [
            'enc' => 'xchacha20poly1305_ietf',
            'typ' => 'JWM/1.0',
            'alg' => $from_verkey ? 'Authcrypt' : 'Anoncrypt',
            'recipients' => $recips
        ];

        return [json_encode($data), $cek];
    }

    /**
     * Locate pack recipient key.
     *
     * @param $recipients
     * @param $my_verKey
     * @param $my_sigKey
     * @throws Exception
     */
    public static function locate_pack_recipient_key($recipients, $my_verKey, $my_sigKey)
    {
        $not_found = [];
        foreach ($recipients as $recipient) {
            if (!key_exists('header', $recipient)
                || !key_exists('encrypted_key', $recipient)
                || !$recipient) {
                throw new Exception('Invalid recipient header');
            }

            $recipient_vk_b58 = $recipient['header']['kid'];

            if (Encryption::bytes_to_b58($my_verKey) != $recipient_vk_b58) {
                array_push($not_found, $recipient_vk_b58);
                continue;
            }

            $pk = sodium_crypto_sign_ed25519_pk_to_curve25519($my_verKey);
            $sk = sodium_crypto_sign_ed25519_pk_to_curve25519($my_sigKey);

            $encrypted_key = Encryption::b64_to_bytes($recipient['encrypted_key'], true);
            if (key_exists('iv', $recipient['header']) && $recipient['header']['iv']
                && key_exists('sender', $recipient['header']) && $recipient['header']['sender'])
            {
                $nonce = Encryption::b64_to_bytes($recipient['header']['iv'], true);
                $enc_sender = Encryption::b64_to_bytes($recipient['header']['sender'], true);
            } else {
                $nonce = null;
                $enc_sender = null;
            }

            if ($nonce && $enc_sender) {
                $sender_vk = mb_convert_encoding(sodium_crypto_box_seal_open($enc_sender, $pk . $sk), 'ASCII');
                $sender_pk = sodium_crypto_sign_ed25519_pk_to_curve25519(Encryption::b58_to_bytes($sender_vk));
                $cek = sodium_crypto_box_open($encrypted_key, $nonce, $sender_pk . $sk);
            } else {
                $sender_vk = null;
                $cek = sodium_crypto_box_seal_open($encrypted_key, $pk . $sk);
            }
            return [$cek, $sender_vk, $recipient_vk_b58];
        }

        throw new Exception("No corresponding recipient key found in $not_found");
    }
}
