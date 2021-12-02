<?php


namespace Siruis\Agent\Wallet\Impl;

use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Errors\Exceptions\SiriusFieldTypeError;
use Siruis\RPC\Parsing;
use Siruis\RPC\RawBytes;

class CryptoProxy extends AbstractCrypto
{
    /**
     * @var \Siruis\Agent\Connections\AgentRPC
     */
    private $rpc;

    /**
     * CryptoProxy constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
    }


    /**
     * @inheritDoc
     */
    public function create_key(string $seed = null, string $crypto_type = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/create_key',
            [
                'seed' => $seed,
                'crypto_type' => $crypto_type
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function set_key_metadata(string $verkey, array $metadata)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/set_key_metadata',
            [
                'verkey' => $verkey,
                'metadata' => $metadata
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function get_key_metadata(string $verkey): ?array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_key_metadata',
            [
                'verkey' => $verkey
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function crypto_sign(string $signer_vk, string $msg): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/crypto_sign',
            [
                'signer_vk' => $signer_vk,
                'msg' => new RawBytes($msg)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function crypto_verify(string $signer_vk, string $msg, string $signature): bool
    {
        if (Parsing::is_binary($signature)) {
            return $this->rpc->remoteCall(
                'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/crypto_verify',
                [
                    'signer_vk' => $signer_vk,
                    'msg' => new RawBytes($msg),
                    'signature' => $signature
                ]
            );
        }

        throw new SiriusFieldTypeError('signature', 'binary', gettype($signature));
    }


    /**
     * @inheritDoc
     */
    public function anon_crypt(string $recipient_vk, string $msg): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/anon_crypt',
            [
                'recipient_vk' => $recipient_vk,
                'msg' => $msg
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function anon_decrypt(string $recipient_vk, string $encrypted_msg): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/anon_decrypt',
            [
                'recipient_vk' => $recipient_vk,
                'encrypted_msg' => $encrypted_msg
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function pack_message($message, array $recipient_verkeys, string $sender_verkey = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/pack_message',
            [
                'recipient_verkeys' => $recipient_verkeys,
                'message' => $message,
                'sender_verkey' => $sender_verkey
            ]
        );
    }


    /**
     * @inheritDoc
     */
    public function unpack_message(string $jwe): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/unpack_message',
            [
                'jwe' => new RawBytes($jwe)
            ]
        );
    }
}
