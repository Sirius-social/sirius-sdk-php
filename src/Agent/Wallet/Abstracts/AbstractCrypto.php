<?php


namespace Siruis\Agent\Wallet\Abstracts;


abstract class AbstractCrypto
{
    /**
     * Creates keys pair and stores in the wallet.
     *
     * @param string|null $seed string, (optional) Seed that allows deterministic key creation (if not set random one will be created). Can be UTF-8, base64 or hex string.
     * @param string|null $crypto_type string, // Optional (if not set then ed25519 curve is used); Currently only 'ed25519' value is supported for this field.
     *
     * @return string Ver key of generated key pair, also used as key identifier
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function create_key(string $seed = null, string $crypto_type = null): string;

    /**
     * Saves/replaces the meta information for the giving key in the wallet.
     *
     * @param string $verkey the key (verkey, key id) to store metadata.
     * @param array $metadata the meta information that will be store with the key.
     *
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function set_key_metadata(string $verkey, array $metadata);

    /**
     * Retrieves the meta information for the giving key in the wallet.
     *
     * @param string $verkey The key (verkey, key id) to retrieve metadata.
     *
     * @return array|null The meta information stored with the key; Can be null if no metadata was saved for this key.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_key_metadata(string $verkey): ?array;

    /**
     * Signs a message with a key.
     *
     * Note to use DID keys with this function you can call indy_key_for_did to get key id (verkey) for specific DID.
     *
     * @param string $signer_vk id (verkey) of my key. The key must be created by calling create_key or create_and_store_my_did
     * @param string $msg a message to be signed
     *
     * @return string a signature string
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function crypto_sign(string $signer_vk, string $msg): string;

    /**
     * Verify a signature with a verkey.
     *
     * Note to use DID keys with this function you can call key_for_did to get key id (verkey) for specific DID.
     *
     * @param string $signer_vk verkey of signer of the message
     * @param string $msg message that has been signed
     * @param string $signature a signature to be verified
     *
     * @return bool true - if signature is valid, false - otherwise
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusFieldTypeError
     */
    abstract public function crypto_verify(string $signer_vk, string $msg, string $signature): bool;

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
     * @param string $recipient_vk verkey of message recipient
     * @param string $msg a message to be signed
     * @return string an encrypted message as an array of bytes
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function anon_crypt(string $recipient_vk, string $msg): string;

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
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function anon_decrypt(string $recipient_vk, string $encrypted_msg): string;

    /**
     * Packs a message by encrypting the message and serializes it in a JWE-like format (Experimental)
     *
     * Note to use DID keys with this function you can call did.key_for_did to get key id (verkey)
     * for specific DID.
     *
     * @param mixed $message the message being sent as a string. If it's JSON formatted it should be converted to a string
     * @param array $recipient_verkeys a list of Strings which are recipient verkeys
     * @param string|null $sender_verkey the sender's verkey as a string. -> When None is passed in this parameter, anoncrypt mode is used
     * @return string an Agent Wire Message format as a byte array.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function pack_message($message, array $recipient_verkeys, string $sender_verkey = null): string;

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
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function unpack_message(string $jwe): array;
}