<?php

namespace Siruis\Encryption;


use Exception;
use RuntimeException;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use TypeError;
use const Sodium\CRYPTO_BOX_NONCEBYTES;

class Ed25519
{
    /**
     * @param $b58_or_bytes
     * @return string
     */
    public static function ensure_is_bytes($b58_or_bytes): string
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
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     * @throws \Exception
     */
    public static function prepare_pack_recipient_keys($to_verkeys, $from_verkey = null, $from_sigkey = null): array
    {
        if (($from_verkey && !$from_sigkey) || ($from_sigkey && !$from_verkey)) {
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
                $sk = sodium_crypto_sign_ed25519_sk_to_curve25519($from_sigkey);

                $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
                $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $target_pk);
                $enc_cek = sodium_crypto_box($cek, $nonce, $keypair);
            } else {
                $enc_sender = null;
                $nonce = null;
                $enc_cek = sodium_crypto_box_seal($cek, $target_pk);
            }

            if ($enc_sender) {
                $ar_sender = Encryption::bytes_to_b64($enc_sender, true);
            } else {
                $ar_sender = null;
            }
            if ($nonce) {
                $iv = Encryption::bytes_to_b64($nonce, true);
            } else {
                $iv = null;
            }

            $recips[] = [
                'encrypted_key' => Encryption::bytes_to_b64($enc_cek, true),
                'header' => [
                    'kid' => Encryption::bytes_to_b58($target_vk),
                    'sender' => $ar_sender,
                    'iv' => $iv
                ]
            ];
        }

        $data = [
            'enc' => 'xchacha20poly1305_ietf',
            'typ' => 'JWM/1.0',
            'alg' => $from_verkey ? 'Authcrypt' : 'Anoncrypt',
            'recipients' => $recips
        ];

        return [json_encode($data, JSON_THROW_ON_ERROR), $cek];
    }

    /**
     * Locate pack recipient key.
     *
     * @param $recipients
     * @param $my_verKey
     * @param $my_sigKey
     * @return array
     * @throws \Exception
     */
    public static function locate_pack_recipient_key($recipients, $my_verKey, $my_sigKey): array
    {
        $not_found = [];
        foreach ($recipients as $recipient) {
            if (!isset($recipient->header, $recipient->encrypted_key) || !$recipient) {
                throw new RuntimeException('Invalid recipient header');
            }

            $recipient_vk_b58 = $recipient->header->kid;

            if (Encryption::bytes_to_b58($my_verKey) !== $recipient_vk_b58) {
                $not_found[] = $recipient_vk_b58;
                continue;
            }

            $pk = sodium_crypto_sign_ed25519_pk_to_curve25519($my_verKey);
            $sk = sodium_crypto_sign_ed25519_sk_to_curve25519($my_sigKey);
            $encrypted_key = Encryption::b64_to_bytes($recipient->encrypted_key, true);
            if (isset($recipient->header->iv, $recipient->header->sender) && $recipient->header->iv && $recipient->header->sender)
            {
                $nonce = Encryption::b64_to_bytes($recipient->header->iv, true);
                $enc_sender = Encryption::b64_to_bytes($recipient->header->sender, true);
            } else {
                $nonce = null;
                $enc_sender = null;
            }

            if ($nonce && $enc_sender) {
                $sender_keys = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
                $sender_vk = mb_convert_encoding(sodium_crypto_box_seal_open($enc_sender, $sender_keys), 'ascii');
                $sender_pk = sodium_crypto_sign_ed25519_pk_to_curve25519(Encryption::b58_to_bytes($sender_vk));
                $cek_keys = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $sender_pk);
                $cek = sodium_crypto_box_open($encrypted_key, $nonce, $cek_keys);
            } else {
                $sender_vk = null;
                $cek_else_keys = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
                $cek = sodium_crypto_box_seal_open($encrypted_key, $cek_else_keys);
            }
            return [$cek, $sender_vk, $recipient_vk_b58];
        }

        throw new RuntimeException("No corresponding recipient key found in $not_found");
    }

    /**
     * Encrypt the payload of a packed message.
     *
     * @param string $message Message to encrypt
     * @param mixed $add_data additional data
     * @param mixed $key Key used for encryption
     *
     * @return array
     * @throws \Exception
     */
    public static function encrypt_plaintext(string $message, $add_data, $key): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
        $message_bin = mb_convert_encoding($message, 'ASCII');
        $output = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($message_bin, $add_data, $nonce, $key);
        $message_len = strlen($message);
        $cipher_text = substr($output, 0, $message_len);
        $tag = substr($output, $message_len);
        return [
            $cipher_text,
            $nonce,
            $tag
        ];
    }

    /**
     * Decrypt the payload of a packed message.
     *
     * @param $cipher_text
     * @param $recipes_bin
     * @param $nonce
     * @param $key
     * @return string
     * @throws \SodiumException
     */
    public static function decrypt_plaintext($cipher_text, $recipes_bin, $nonce, $key): string
    {
        $output = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($cipher_text, $recipes_bin, $nonce, $key);
        return mb_convert_encoding($output, 'ASCII');
    }

    /**
     * Assemble a packed message for a set of recipients, optionally including the sender.
     *
     * @param $message
     * @param array $to_ver_keys
     * @param $from_ver_key
     * @param $from_sig_key
     * @return string
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     * @throws \Exception
     */
    public static function pack_message($message, array $to_ver_keys, $from_ver_key, $from_sig_key): string
    {
        $tvk = [];
        foreach ($to_ver_keys as $vk) {
            $tvk[] = self::ensure_is_bytes($vk);
        }
        $from_ver_key = self::ensure_is_bytes($from_ver_key);
        $from_sig_key = self::ensure_is_bytes($from_sig_key);
        [$recips_json, $cek] = self::prepare_pack_recipient_keys($tvk, $from_ver_key, $from_sig_key);
        $recips_b64 = Encryption::bytes_to_b64(mb_convert_encoding($recips_json, 'ASCII'), true);
        [$cipher_text, $nonce, $tag] = self::encrypt_plaintext($message, mb_convert_encoding($recips_b64, 'ASCII'), $cek);
        $data =  [
            'protected' => $recips_b64,
            'iv' => Encryption::bytes_to_b64($nonce, true),
            'ciphertext' => Encryption::bytes_to_b64($cipher_text, true),
            'tag' => Encryption::bytes_to_b64($tag, true)
        ];
        return mb_convert_encoding(json_encode($data, JSON_THROW_ON_ERROR), 'ASCII');
    }

    /**
     *
     * Decode a packed message.
     *
     * Disassemble and unencrypt a packed message, returning the message content,
     * verification key of the sender (if available), and verification key of the
     * recipient.
     *
     * @param $enc_message
     * @param $my_verkey
     * @param $my_sigkey
     *
     * @return array
     * @throws \Exception
     */
    public static function unpack_message($enc_message, $my_verkey, $my_sigkey): array
    {
        $my_verkey = self::ensure_is_bytes($my_verkey);
        $my_sigkey = self::ensure_is_bytes($my_sigkey);

        if (!((is_string($enc_message) &&
            (is_object(json_decode($enc_message, false, 512, JSON_THROW_ON_ERROR)) ||
                is_array(json_decode($enc_message, false, 512, JSON_THROW_ON_ERROR)))))) {
            throw new TypeError('Expected bytes or dict, got ' . gettype($enc_message));
        }

        $enc_message = json_decode($enc_message, false, 512, JSON_THROW_ON_ERROR);
        $protected_bin = mb_convert_encoding($enc_message->protected, 'ASCII');
        $recips_json = Encryption::b64_to_bytes($enc_message->protected, true);
        try {
            $recips_outer = json_decode($recips_json, false, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $exception) {
            throw new RuntimeException('Invalid packed message recipients ' . $exception->getMessage());
        }
        $alg = $recips_outer->alg;
        $is_authcrypt = $alg === 'Authcrypt';
        if (!$is_authcrypt && $alg !== 'Anoncrypt') {
            throw new RuntimeException('Unsupported pack algorithm: ' . $alg);
        }
        [$cek, $sender_vk, $recip_vk] = self::locate_pack_recipient_key($recips_outer->recipients, $my_verkey, $my_sigkey);
        if (!$sender_vk && $is_authcrypt) {
            throw new RuntimeException('Sender public key not provided for Authcrypt message');
        }

        $ciphertext = Encryption::b64_to_bytes($enc_message->ciphertext, true);
        $nonce = Encryption::b64_to_bytes($enc_message->iv, true);
        $tag = Encryption::b64_to_bytes($enc_message->tag, true);

        $payload_bin = $ciphertext . $tag;
        $message = self::decrypt_plaintext($payload_bin, $protected_bin, $nonce, $cek);

        return [$message, $sender_vk, $recip_vk];
    }
}
