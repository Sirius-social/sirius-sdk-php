<?php

namespace Siruis\Encryption;


use Siruis\Errors\Exceptions\SiriusCryptoError;
use SodiumException;

class P2PConnection
{
    /**
     * @var array
     */
    protected $my_keys;

    /**
     * @var string
     */
    protected $their_verkey;

    /**
     * P2PConnection constructor.
     *
     * @param array $my_keys (verkey, sigkey) for encrypt/decrypt operations
     * @param string $their_verkey verkey of the counterparty
     */
    public function __construct(array $my_keys, string $their_verkey)
    {
        $this->my_keys = $my_keys;
        $this->their_verkey = $their_verkey;
    }

    /**
     * Encrypt message
     *
     * @param array $message
     *
     * @return string
     * @throws SiriusCryptoError|SodiumException
     */
    public function pack(array $message)
    {
        $to_verkeys = [$this->their_verkey];
        $from_verkey = $this->my_keys[0];
        $from_sigkey = $this->my_keys[1];
        return Ed25519::pack_message(
            json_encode($message),
            $to_verkeys,
            $from_verkey,
            $from_sigkey
        );
    }

    /**
     * Decrypt message
     *
     * @param $enc_message
     *
     * @return mixed
     * @throws SiriusCryptoError
     */
    public function unpack($enc_message)
    {
        try {
            if (is_array($enc_message)) {
                $enc_message = json_encode($enc_message);
            }
            $unpacked = Ed25519::unpack_message($enc_message, $this->my_keys[0], $this->my_keys[1]);
        } catch (\Exception $exception) {
            throw new SiriusCryptoError($exception->getMessage());
        }
        return $unpacked[0];
    }
}