<?php


namespace Siruis\Agent\Wallet\Abstracts;


abstract class AbstractCrypto
{
    /**
     * Creates keys pair and stores in the wallet.
     *
     * @param string|null $seed string, (optional) Seed that allows deterministic key creation (if not set random one will be created). Can be UTF-8, base64 or hex string.
     * @param string|null $cryptoType string, // Optional (if not set then ed25519 curve is used); Currently only 'ed25519' value is supported for this field.
     *
     * @return string Ver key of generated key pair, also used as key identifier
     */
    public abstract function createKey(string $seed = null, string $cryptoType = null): string;

    /**
     * Saves/replaces the meta information for the giving key in the wallet.
     *
     * @param string $verkey the key (verkey, key id) to store metadata.
     * @param array $metadata the meta information that will be store with the key.
     *
     * @return null
     */
    public abstract function setKeyMetadata(string $verkey, array $metadata);

    /**
     * Retrieves the meta information for the giving key in the wallet.
     *
     * @param string $verkey The key (verkey, key id) to retrieve metadata.
     *
     * @return array|null The meta information stored with the key; Can be null if no metadata was saved for this key.
     */
    public abstract function getKeyMetadata(string $verkey);

    /**
     * Signs a message with a key.
     *
     * Note to use DID keys with this function you can call indy_key_for_did to get key id (verkey) for specific DID.
     *
     * @param string $signerVk id (verkey) of my key. The key must be created by calling create_key or create_and_store_my_did
     * @param string $msg a message to be signed
     *
     * @return string a signature string
     */
    public abstract function cryptoSign(string $signerVk, string $msg): string;

    /**
     * Verify a signature with a verkey.
     *
     * Note to use DID keys with this function you can call key_for_did to get key id (verkey) for specific DID.
     *
     * @param string $signerVk verkey of signer of the message
     * @param string $msg message that has been signed
     * @param string $signature a signature to be verified
     *
     * @return bool true - if signature is valid, false - otherwise
     */
    public abstract function cryptoVerify(string $signerVk, string $msg, string $signature): bool;

    /**
     * Encrypts a message by anonymous-encryption scheme.
     *
     * Sealed boxes are designed to anonymously send messages to a Recipient given its public key.
     * Only the Recipient can decrypt these messages, using its private key.
     * While the Recipient can verify the integrity of the message, it cannot verify the identity of the Sender.
     *
     * Note to use DID keys with this function you can call key_for_did to get key id (verkey)
     * for specific DID.
     *
     * Note: use pack_message function for A2A goals.
     *
     * @param string $recipientVk verkey of message recipient
     * @param string $msg a message to be signed
     * @return string an encrypted message as an array of bytes
     */
    public abstract function anonCrypt(string $recipientVk, string $msg): string;

    /**
     * Decrypts a message by anonymous-encryption scheme.
     *
     * Sealed boxes are designed to anonymously send messages to a Recipient given its public key.
     * Only the Recipient can decrypt these messages, using its private key.
     * While the Recipient can verify the integrity of the message, it cannot verify the identity of the Sender.
     *
     * Note to use DID keys with this function you can call key_for_did to get key id (verkey)
     * for specific DID.
     *
     * Note: use unpack_message function for A2A goals.
     *
     *
     * @param string $recipient_vk id (verkey) of my key. The key must be created by calling indy_create_key or create_and_store_my_did
     * @param string $encrypted_msg encrypted message
     * @return string  decrypted message as an array of bytes
     */
    public abstract function anonDecrypt(string $recipient_vk, string $encrypted_msg): string;

    /**
     * Packs a message by encrypting the message and serializes it in a JWE-like format (Experimental)
     *
     * Note to use DID keys with this function you can call did.key_for_did to get key id (verkey)
     * for specific DID.
     *
     * @param mixed $message the message being sent as a string. If it's JSON formatted it should be converted to a string
     * @param array $recipientVerkeys a list of Strings which are recipient verkeys
     * @param string|null $sender_verkey the sender's verkey as a string. -> When None is passed in this parameter, anoncrypt mode is used
     * @return string an Agent Wire Message format as a byte array.
     */
    public abstract function pack_message($message, array $recipientVerkeys, string $sender_verkey = null): string;

    /**
     * Unpacks a JWE-like formatted message outputted by pack_message (Experimental)
     *
     * #Returns:
     * (Authcrypt mode)
     *
     * {
     *   "message": <decrypted message>,
     *   "recipient_verkey": <recipient verkey used to decrypt>,
     *   "sender_verkey": <sender verkey used to encrypt>
     * }
     *
     * (Anoncrypt mode)
     *
     * {
     *   "message": <decrypted message>,
     *   "recipient_verkey": <recipient verkey used to decrypt>,
     * }
     *
     * @param string $jwe
     * @return array
     */
    public abstract function unpackMessage(string $jwe): array;
}