<?php


namespace Siruis\Agent\AriesRFC;


use DateTime;
use Exception;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Encryption\Encryption;

class Utils
{
    /**
     * @param string $s
     * @param bool $throw_exceptions
     * @return \DateTime|false|null
     */
    public static function str_to_utc(string $s, bool $throw_exceptions = true)
    {
        $tz_shift = null;
        try {
            if (strpos($s, '+')) {
                [$s, $shift] = explode('+', $s);
                $now = new DateTime();
                $shift = DateTime::createFromFormat('h', $shift);
                $tz_shift = $now->diff($shift);
            }
            $s = str_replace('T', ' ', $s);
            return DateTime::createFromFormat('Y-m-d h:i:s', $s . $tz_shift);
        } catch (Exception $exception) {
            printf('Error while parse datetime');
            if ($throw_exceptions) {
                throw $exception;
            }

            return null;
        }
    }
    
    /**
     * @param DateTime $dt
     *
     * @return string
     */
    public static function utc_to_str(DateTime $dt): string
    {
        return $dt->format('Y-m-d H:i:s') . '+0000';
    }

    /**
     * @param AbstractCrypto $crypto
     * @param $value
     * @param string $verkey
     * @param bool $exclude_sig_data
     * @return array
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \SodiumException
     */
    public static function sign(AbstractCrypto $crypto, $value, string $verkey, bool $exclude_sig_data = false): array
    {
        $timestamp_bytes = pack('Q', time());

        $sig_data_bytes = $timestamp_bytes . json_encode($value, JSON_THROW_ON_ERROR);
        $sig_data = Encryption::bytes_to_b64($sig_data_bytes, true);

        $signature_bytes = $crypto->crypto_sign($verkey, $sig_data);
        $signature = Encryption::bytes_to_b64($signature_bytes, true);

        $data = [
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/signature/1.0/ed25519Sha512_single',
            'signer' => $verkey,
            'signature' => $signature
        ];

        if (!$exclude_sig_data) {
            $data['sig_data'] = $sig_data;
        }

        return $data;
    }

    /**
     * @param AbstractCrypto $crypto
     * @param array $signed
     *
     * @return array
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusFieldTypeError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \SodiumException
     */
    public static function verify_signed(AbstractCrypto $crypto, array $signed): array
    {
        $signature_bytes = Encryption::b64_to_bytes(mb_convert_encoding($signed['signature'], 'ascii'), true);
        $sig_data_bytes = Encryption::b64_to_bytes(mb_convert_encoding($signed['sig_data'], 'ascii'), true);
        $sig_verified = $crypto->crypto_verify($signed['signer'], $sig_data_bytes, $signature_bytes);
        $field_json = substr($sig_data_bytes, 8);
        return [
            json_decode($field_json, false, 512, JSON_THROW_ON_ERROR),
            $sig_verified
        ];
    }
}