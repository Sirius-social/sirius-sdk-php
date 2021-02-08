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
     */
    public abstract function create_and_store_my_did(string $did = null, string $seed = null, bool $cid = null): array;

    public abstract function store_their_did(string $did, string $verkey = null);

    public abstract function set_did_metadata(string $did, array $metadata = null);

    public abstract function list_my_dids_with_meta(): array;

    public abstract function get_did_metadata($did): ?array;

    public abstract function key_for_local_did(string $did): string;

    public abstract function key_for_did(string $pool_name, string $did): string;

    public abstract function create_key(string $seed = null): string;

    public abstract function replce_keys_start(string $did, string $seed = null): string;

    public abstract function replace_keys_apply(string $did);

    public abstract function set_key_metadata(string $verkey, array $metadata);

    public abstract function get_key_metadata(string $verkey): array;

    public abstract function set_endpoint_for_did(string $did, string $address, string $transport_key);

    public abstract function get_endpoint_for_did(string $pool_name, string $did);

    public abstract function get_my_did_with_meta(string $did);

    public abstract function abbreviate_verkey(string $did, string $full_verkey): string;

    public abstract function qualify_did(string $did, string $method): string;
}