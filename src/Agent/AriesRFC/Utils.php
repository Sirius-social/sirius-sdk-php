<?php


namespace Siruis\Agent\AriesRFC;


use DateTime;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Encryption\Encryption;
use SodiumException;

class Utils
{
    /**
     * @param DateTime $dt
     *
     * @return string
     */
    public static function utc_to_str(DateTime $dt)
    {
        return $dt->format('Y-m-d H:i:s') . '+0000';
    }

    public static function str_to_utc()
    {
        
    }

    /**
     * @param AbstractCrypto $crypto
     * @param $value
     * @param string $verkey
     * @param bool $exclude_sig_data
     * @return array
     * @throws SodiumException
     */
    public static function sign(AbstractCrypto $crypto, $value, string $verkey, bool $exclude_sig_data = false): array
    {
        $timestamp_bytes = pack('Q', time());

        $sig_data_bytes = $timestamp_bytes . json_decode($value);
        $sig_data = Encryption::b64_to_bytes($sig_data_bytes, true);

        $signature_bytes = $crypto->cryptoSign($verkey, $sig_data);
        $signature = Encryption::b64_to_bytes($signature_bytes, true);

        return [
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/signature/1.0/ed25519Sha512_single',
            'signer' => $verkey,
            'signature' => $signature
        ];
    }

    /**
     * @param AbstractCrypto $crypto
     * @param array $signed
     *
     * @return mixed|bool
     * @throws SodiumException
     */
    public static function verify_signed(AbstractCrypto $crypto, array $signed)
    {
        $signature_bytes = Encryption::b64_to_bytes($signed['signature'], true);
        $sig_data_bytes = Encryption::b64_to_bytes($signed['sig_data'], true);
        $sig_verified = $crypto->cryptoVerify($signed['signer'], $sig_data_bytes, $signature_bytes);
        $timestamp = unpack('Q', substr($sig_data_bytes, 0, 8));
        $field_json = substr($sig_data_bytes, 8);
        return [
            json_decode($field_json),
            $sig_verified
        ];
    }
}