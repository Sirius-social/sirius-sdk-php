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
        $this->service = Hub::current_hub()->get_crypto();
    }

    /**
     * @inheritDoc
     */
    public function createKey(string $seed = null, string $cryptoType = null): string
    {
        return $this->service->createKey($seed, $cryptoType);
    }

    /**
     * @inheritDoc
     */
    public function setKeyMetadata(string $verkey, array $metadata)
    {
        return $this->service->setKeyMetadata($verkey, $metadata);
    }

    /**
     * @inheritDoc
     */
    public function getKeyMetadata(string $verkey): ?array
    {
        return $this->service->getKeyMetadata($verkey);
    }

    /**
     * @inheritDoc
     */
    public function cryptoSign(string $signerVk, string $msg): string
    {
        return $this->service->cryptoSign($signerVk, $msg);
    }

    /**
     * @inheritDoc
     */
    public function cryptoVerify(string $signerVk, string $msg, string $signature): bool
    {
        return $this->service->cryptoVerify($signerVk, $msg, $signature);
    }

    /**
     * @inheritDoc
     */
    public function anonCrypt(string $recipientVk, string $msg): string
    {
        return $this->service->anonCrypt($recipientVk, $msg);
    }

    /**
     * @inheritDoc
     */
    public function anonDecrypt(string $recipient_vk, string $encrypted_msg): string
    {
        return $this->service->anonDecrypt($recipient_vk, $encrypted_msg);
    }

    /**
     * @inheritDoc
     */
    public function pack_message($message, array $recipientVerkeys, string $sender_verkey = null): string
    {
        return $this->service->pack_message($message, $recipientVerkeys, $sender_verkey);
    }

    /**
     * @inheritDoc
     */
    public function unpackMessage(string $jwe): array
    {
        return $this->service->unpackMessage($jwe);
    }
}