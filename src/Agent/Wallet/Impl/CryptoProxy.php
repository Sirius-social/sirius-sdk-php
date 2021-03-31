<?php


namespace Siruis\Agent\Wallet\Impl;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;

class CryptoProxy extends AbstractCrypto
{
    /**
     * @var AgentRPC
     */
    private $rpc;

    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
    }

    /**
     * @inheritDoc
     */
    public function createKey(string $seed = null, string $cryptoType = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/create_key',
            [
                'seed' => $seed,
                'crypto_type' => $cryptoType
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function setKeyMetadata(string $verkey, array $metadata)
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
    public function getKeyMetadata(string $verkey): ?array
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
    public function cryptoSign(string $signerVk, string $msg): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/crypto_sign',
            [
                'signer_vk' => $signerVk,
                'msg' => $msg
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function cryptoVerify(string $signerVk, string $msg, string $signature): bool
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/crypto_verify',
            [
                'signer_vk' => $signerVk,
                'msg' => $msg,
                'signature' => $signature
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function anonCrypt(string $recipientVk, string $msg): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/anon_crypt',
            [
                'recipient_vk' => $recipientVk,
                'msg' => $msg
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function anonDecrypt(string $recipient_vk, string $encrypted_msg): string
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
    public function pack_message($message, array $recipientVerkeys, string $sender_verkey = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/pack_message',
            [
                'recipient_verkeys' => $recipientVerkeys,
                'message' => $message,
                'sender_verkey' => $sender_verkey
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function unpackMessage(string $jwe): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/unpack_message',
            [
                'jwe' => $jwe
            ]
        );
    }
}