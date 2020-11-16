<?php

namespace Siruis\Encryption;


use Exception;
use ParagonIE_Sodium_Compat;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use SodiumException;

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
     * @throws SiriusCryptoError
     * @throws Exception
     */
    public static function prepare_pack_recipient_keys($to_verkeys, $from_verkey = null, $from_sigkey = null)
    {
        if ($from_verkey && !$from_sigkey || $from_sigkey && !$from_verkey) {
            throw new SiriusCryptoError('Both verkey and sigkey needed to authenticated encrypt message');
        }

        $cek = ParagonIE_Sodium_Compat::crypto_secretstream_xchacha20poly1305_keygen();
        $recips = [];

        foreach ($to_verkeys as $target_vk) {

            $target_pk = ParagonIE_Sodium_Compat::crypto_sign_ed25519_pk_to_curve25519($target_vk);

            if ($from_verkey) {
                $b58_from_verkey = Encryption::bytes_to_b58($from_verkey);
                $sender_vk = mb_convert_encoding($b58_from_verkey, 'ASCII');
                $enc_sender = ParagonIE_Sodium_Compat::crypto_box_seal($sender_vk, $target_pk);
                $sk = ParagonIE_Sodium_Compat::crypto_sign_ed25519_sk_to_curve25519($from_sigkey);
                $nonce = random_bytes(ParagonIE_Sodium_Compat::CRYPTO_BOX_NONCEBYTES);
                $keypair = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey($target_pk, $sk);
                $enc_cek = ParagonIE_Sodium_Compat::crypto_box($cek, $nonce, $keypair);
            } else {
                $enc_sender = null;
                $nonce = null;
                $enc_cek = ParagonIE_Sodium_Compat::crypto_box_seal($cek, $target_pk);
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
     * @return array
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

            $recipient_vk_b58 = $recipient->header->kid;

            if (Encryption::bytes_to_b58($my_verKey) != $recipient_vk_b58) {
                array_push($not_found, $recipient_vk_b58);
                continue;
            }

            $pk = ParagonIE_Sodium_Compat::crypto_sign_ed25519_pk_to_curve25519($my_verKey);
            $sk = ParagonIE_Sodium_Compat::crypto_sign_ed25519_sk_to_curve25519($my_sigKey);
            $encrypted_key = Encryption::b64_to_bytes($recipient->encrypted_key, true);
            if (key_exists('iv', $recipient->header) && $recipient->header->iv
                && key_exists('sender', $recipient->header) && $recipient->header->sender)
            {
                $nonce = Encryption::b64_to_bytes($recipient->header->iv, true);
                $enc_sender = Encryption::b64_to_bytes($recipient->header->sender, true);
            } else {
                $nonce = null;
                $enc_sender = null;
            }

            if ($nonce && $enc_sender) {
                $sender_keys = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
                $sender_vk = mb_convert_encoding(ParagonIE_Sodium_Compat::crypto_box_seal_open($enc_sender, $sender_keys), 'ascii');
                $sender_pk = ParagonIE_Sodium_Compat::crypto_sign_ed25519_pk_to_curve25519(Encryption::b58_to_bytes($sender_vk));
                $cek_keys = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey($sk, $sender_pk);
                $cek = ParagonIE_Sodium_Compat::crypto_box_open($encrypted_key, $nonce, $cek_keys);
            } else {
                $sender_vk = null;
                $cek_else_keys = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
                $cek = ParagonIE_Sodium_Compat::crypto_box_seal_open($encrypted_key, $cek_else_keys);
            }
            var_dump($cek);
            return [$cek, $sender_vk, $recipient_vk_b58];
        }

        echo new Exception("No corresponding recipient key found in $not_found");
    }

    /**
     * Encrypt the payload of a packed message.
     *
     * @param string $message Message to encrypt
     * @param mixed $add_data additional data
     * @param mixed $key Key used for encryption
     *
     * @return array
     * @throws Exception
     */
    public static function encrypt_plaintext(string $message, $add_data, $key)
    {
        $nonce = random_bytes(ParagonIE_Sodium_Compat::CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
        $message_bin = mb_convert_encoding($message, 'ASCII');
        $output = ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_ietf_encrypt($message_bin, $add_data, $nonce, $key);
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
     *
     * @return string
     * @throws SodiumException
     */
    public static function decrypt_plaintext($cipher_text, $recipes_bin, $nonce, $key)
    {
        $output = ParagonIE_Sodium_Compat::crypto_aead_chacha20poly1305_ietf_decrypt($cipher_text, $recipes_bin, $nonce, $key);
        return mb_convert_encoding($output, 'ASCII');
    }

    /**
     * Assemble a packed message for a set of recipients, optionally including the sender.
     *
     * @param $message
     * @param array $to_ver_keys
     * @param $from_ver_key
     * @param $from_sig_key
     *
     * @return string
     * @throws SiriusCryptoError|SodiumException
     * @throws Exception
     */
    public static function pack_message($message, array $to_ver_keys, $from_ver_key, $from_sig_key)
    {
        $tvk = [];
        foreach ($to_ver_keys as $vk) {
            array_push($tvk, self::ensure_is_bytes($vk));
        }
        $from_ver_key = self::ensure_is_bytes($from_ver_key);
        $from_sig_key = self::ensure_is_bytes($from_sig_key);
        $prepared = self::prepare_pack_recipient_keys($tvk, $from_ver_key, $from_sig_key);
        $cek = $prepared[1];
        $recips_json = $prepared[0];
        $recips_b64 = Encryption::bytes_to_b64(mb_convert_encoding($recips_json, 'ASCII'), true);
        $encrypted = self::encrypt_plaintext($message, mb_convert_encoding($recips_b64, 'ASCII'), $cek);
        $cipher_text = $encrypted[0];
        $nonce = $encrypted[1];
        $tag = $encrypted[2];
        $data =  [
            'protected' => $recips_b64,
            'iv' => Encryption::bytes_to_b64($nonce, true),
            'ciphertext' => Encryption::bytes_to_b64($cipher_text, true),
            'tag' => Encryption::bytes_to_b64($tag, true)
        ];
        return mb_convert_encoding(json_encode($data), 'ASCII');
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
     * @throws Exception
     */
    public static function unpack_message($enc_message, $my_verkey, $my_sigkey)
    {
        $my_verkey = self::ensure_is_bytes($my_verkey);
        $my_sigkey = self::ensure_is_bytes($my_sigkey);

        if (!((is_string($enc_message) &&
            (is_object(json_decode($enc_message)) ||
                is_array(json_decode($enc_message)))))) {
            throw new \TypeError('Expected bytes or dict, got ' . gettype($enc_message));
        }

        $enc_message = json_decode($enc_message);
        $protected_bin = mb_convert_encoding($enc_message->protected, 'ASCII');
        $recips_json = Encryption::b64_to_bytes($enc_message->protected, true);
        $recips_outer = $recips_json;
        try {
            $recips_outer = json_decode($recips_json);
        } catch (Exception $exception) {
            throw new Exception('Invalid packed message recipients ' . $exception->getMessage());
        }
        $alg = $recips_outer->alg;
        $is_authcrypt = $alg == 'Authcrypt';
        if (!$is_authcrypt && $alg != 'Anoncrypt') {
            throw new Exception('Unsupported pack algorithm: ' . $alg);
        }
        $located = self::locate_pack_recipient_key($recips_outer->recipients, $my_verkey, $my_sigkey);
        $cek = $located[0];
        $sender_vk = $located[1];
        $recip_vk = $located[2];
        if (!$sender_vk && $is_authcrypt) {
            throw new Exception('Sender public key not provided for Authcrypt message');
        }

        $ciphertext = Encryption::b64_to_bytes($enc_message->ciphertext, true);
        $nonce = Encryption::b64_to_bytes($enc_message->iv, true);
        $tag = Encryption::b64_to_bytes($enc_message->tag, true);

        $payload_bin = $ciphertext . $tag;
        $message = self::decrypt_plaintext($payload_bin, $protected_bin, $nonce, $cek);

        return [$message, $sender_vk, $recip_vk];
    }
}
