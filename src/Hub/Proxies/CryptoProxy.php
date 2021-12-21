<?php


namespace Siruis\Hub\Proxies;


use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Hub\Core\Hub;

class CryptoProxy extends AbstractCrypto
{
    /**
     * @var AbstractCrypto
     */
    protected $service;

    /**
     * CryptoProxy constructor.
     * @throws SiriusInitializationError
     */
    public function __construct()
    {
        $hub = Hub::current_hub();
        if (is_null($hub)) {
            throw new SiriusInitializationError('Hub not initialized');
        }
        $this->service = $hub->get_crypto();
    }

    /**
     * @inheritDoc
     */
    public function create_key(string $seed = null, string $crypto_type = null): string
    {
        return $this->service->create_key($seed, $crypto_type);
    }

    /**
     * @inheritDoc
     */
    public function set_key_metadata(string $verkey, array $metadata)
    {
        return $this->service->set_key_metadata($verkey, $metadata);
    }

    /**
     * @inheritDoc
     */
    public function get_key_metadata(string $verkey): ?array
    {
        return $this->service->get_key_metadata($verkey);
    }

    /**
     * @inheritDoc
     */
    public function crypto_sign(string $signer_vk, string $msg): string
    {
        return $this->service->crypto_sign($signer_vk, $msg);
    }

    /**
     * @inheritDoc
     */
    public function crypto_verify(string $signer_vk, string $msg, string $signature): bool
    {
        return $this->service->crypto_verify($signer_vk, $msg, $signature);
    }

    /**
     * @inheritDoc
     */
    public function anon_crypt(string $recipient_vk, string $msg): string
    {
        return $this->service->anon_crypt($recipient_vk, $msg);
    }

    /**
     * @inheritDoc
     */
    public function anon_decrypt(string $recipient_vk, string $encrypted_msg): string
    {
        return $this->service->anon_decrypt($recipient_vk, $encrypted_msg);
    }

    /**
     * @inheritDoc
     */
    public function pack_message($message, array $recipient_verkeys, string $sender_verkey = null): string
    {
        return $this->service->pack_message($message, $recipient_verkeys, $sender_verkey);
    }

    /**
     * @inheritDoc
     */
    public function unpack_message(string $jwe): array
    {
        return $this->service->unpack_message($jwe);
    }
}