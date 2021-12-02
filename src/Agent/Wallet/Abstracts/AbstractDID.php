<?php


namespace Siruis\Agent\Wallet\Abstracts;


abstract class AbstractDID
{
    /**
     * Creates keys (signing and encryption keys) for a new
     * DID (owned by the caller of the library).
     * Identity's DID must be either explicitly provided, or taken as the first 16 bit of verkey.
     * Saves the Identity DID with keys in a secured Wallet, so that it can be used to sign
     * and encrypt transactions.
     *
     * @param string|null $did (optional)
     * if not provided and cid param is false then the first 16 bit of the verkey will be
     * used as a new DID;
     * if not provided and cid is true then the full verkey will be used as a new DID;
     * if provided, then keys will be replaced - key rotation use case)
     * @param string|null $seed (optional) Seed that allows deterministic key creation
     * (if not set random one will be created).
     * Can be UTF-8, base64 or hex string.
     * @param bool|null $cid (optional; if not set then false is used;)
     * @return array DID and verkey (for verification of signature)
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function create_and_store_my_did(string $did = null, string $seed = null, bool $cid = null): array;

    /**
     * Saves their DID for a pairwise connection in a secured Wallet,
     * so that it can be used to verify transaction.
     * Updates DID associated verkey in case DID already exists in the Wallet.
     *
     * @param string $did string, (required)
     * @param string|null $verkey string (optional, if only pk is provided),
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function store_their_did(string $did, string $verkey = null);

    /**
     * Saves/replaces the meta information for the giving DID in the wallet.
     *
     * @param string $did the DID to store metadata.
     * @param array|null $metadata the meta information that will be store with the DID.
     * @return mixed Error code
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function set_did_metadata(string $did, array $metadata = null);

    /**
     * List DIDs and metadata stored in the wallet.
     *
     * @return array List of DIDs with verkeys and meta data.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function list_my_dids_with_meta(): array;

    /**
     * Retrieves the meta information for the giving DID in the wallet.
     *
     * @param mixed $did The DID to retrieve metadata.
     * @return array|null The meta information stored with the DID; Can be null if no metadata was saved for this DID.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_did_metadata($did): ?array;

    /**
     * Returns ver key (key id) for the given DID.
     *
     * "key_for_local_did" call looks data stored in the local wallet only and skips freshness checking.
     *
     * Note if you want to get fresh data from the ledger you can use "key_for_did" call instead.
     *
     * Note that "create_and_store_my_did" makes similar wallet record as "create_key".
     * As result we can use returned ver key in all generic crypto and messaging functions.
     *
     * @param string $did The DID to resolve key.
     * @return string The DIDs ver key (key id).
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function key_for_local_did(string $did): string;

    /**
     * Returns ver key (key id) for the given DID.
     * "key_for_did" call follow the idea that we resolve information about their DID from
     * the ledger with cache in the local wallet. The "open_wallet" call has freshness parameter
     * that is used for checking the freshness of cached pool value.
     *
     * Note if you don't want to resolve their DID info from the ledger you can use
     * "key_for_local_did" call instead that will look only to local wallet and skip
     * freshness checking.
     *
     * Note that "create_and_store_my_did" makes similar wallet record as "create_key".
     * As result we can use returned ver key in all generic crypto and messaging functions.
     *
     * @param string $pool_name Pool Name.
     * @param string $did The DID to resolve key.
     * @return string The DIDs ver key (key id).
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function key_for_did(string $pool_name, string $did): string;

    /**
     * Creates keys pair and stores in the wallet.
     *
     * @param string|null $seed string, (optional) Seed that allows deterministic key creation
     *                          (if not set random one will be created).
     *                          Can be UTF-8, base64 or hex string.
     * @return string Ver key of generated key pair, also used as key identifier
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function create_key(string $seed = null): string;

    /**
     * Generated new keys (signing and encryption keys) for an existing
     * DID (owned by the caller of the library).
     *
     * @param string $did signing DID
     * @param string|null $seed string, (optional) Seed that allows deterministic key creation
     *                          (if not set random one will be created). Can be UTF-8, base64 or hex string.
     * @return string verkey
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function replce_keys_start(string $did, string $seed = null): string;

    /**
     * Apply temporary keys as main for an existing DID (owned by the caller of the library).
     *
     * @param string $did The DID to resolve key.
     * @return mixed Error code
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function replace_keys_apply(string $did);

    /**
     * Creates keys pair and stores in the wallet.
     *
     * @param string $verkey the key (verkey, key id) to store metadata.
     * @param array $metadata the meta information that will be store with the key.
     * @return mixed Error code
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
     * @return array The meta information stored with the key; Can be null if no metadata was saved for this key.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_key_metadata(string $verkey): array;

    /**
     * Set/replaces endpoint information for the given DID.
     *
     * @param string $did The DID to resolve endpoint.
     * @param string $address The DIDs endpoint address.
     * @param string $transport_key The DIDs transport key (ver key, key id).
     * @return mixed Error code
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function set_endpoint_for_did(string $did, string $address, string $transport_key);

    /**
     * Returns endpoint information for the given DID.
     *
     * @param string $pool_name Pool name.
     * @param string $did The DID to resolve endpoint.
     * @return mixed (endpoint, transport_vk)
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_endpoint_for_did(string $pool_name, string $did);

    /**
     * Get DID metadata and verkey stored in the wallet.
     *
     * @param string $did The DID to retrieve metadata.
     * @return mixed DID with verkey and metadata.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_my_did_with_meta(string $did);

    /**
     * Retrieves abbreviated verkey if it is possible otherwise return full verkey.
     *
     * @param string $did The DID.
     * @param string $full_verkey The DIDs verification key,
     * @return string Either abbreviated or full verkey.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function abbreviate_verkey(string $did, string $full_verkey): string;

    /**
     * Update DID stored in the wallet to make fully qualified, or to do other DID maintenance.
     *      - If the DID has no prefix, a prefix will be appended (prepend did:peer to a legacy did)
     *      - If the DID has a prefix, a prefix will be updated (migrate did:peer to did:peer-new)
     *
     * Update DID related entities stored in the wallet.
     *
     * @param string $did target DID stored in the wallet.
     * @param string $method method to apply to the DID.
     * @return string fully qualified did
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function qualify_did(string $did, string $method): string;
}