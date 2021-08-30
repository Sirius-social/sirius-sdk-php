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

        $sig_data_bytes = $timestamp_bytes . json_encode($value);
        $sig_data = Encryption::bytes_to_b64($sig_data_bytes, true);

        $signature_bytes = $crypto->cryptoSign($verkey, $sig_data);
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
     * @return mixed|bool
     * @throws SodiumException
     */
    public static function verify_signed(AbstractCrypto $crypto, array $signed)
    {

        $signature_bytes = self::urlsafe_b64decode(mb_convert_encoding($signed['signature'], 'ascii'));
        $sig_data_bytes = self::urlsafe_b64decode(mb_convert_encoding($signed['sig_data'], 'ascii'));
        $sig_verified = $crypto->cryptoVerify($signed['signer'], $sig_data_bytes, $signature_bytes);
        $field_json = substr($sig_data_bytes, 8);
        return [
            json_decode($field_json),
            $sig_verified
        ];
    }

    public static function urlsafe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

    public static function urlsafe_b64decode($string)
    {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}