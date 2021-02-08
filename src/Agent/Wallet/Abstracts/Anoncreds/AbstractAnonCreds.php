<?php


namespace Siruis\Agent\Wallet\Abstracts\Anoncreds;


abstract class AbstractAnonCreds
{
    public abstract function issuer_create_schema(string $issuer_did, string $name, string $version, array $attrs);

    public abstract function issuer_create_and_store_credential_def(
        string $issuer_did, array $schema, string $tag, string $signature_type = null, array $config = null
    );

    public abstract function issuer_rotate_credential_def_start(string $cred_def_id, array $config = null): array;

    public abstract function issuer_rotate_credential_def_apply(string $cred_def_id);

    public abstract function issuer_create_and_store_revoc_reg(
        string $issuer_did, string $revoc_def_type, string $tag, string $cred_def_id,
        array $config, int $tails_writer_handle
    );

    public abstract function issuer_create_credential_offer(string $cred_def_id): array;

    public abstract function issuer_create_credential(
        array $cred_offer, array $cred_req, array $cred_values, string $rev_reg_id = null,
        int $blob_storage_reader_handle = null
    );

    public abstract function issuer_revoke_credential(
        int $blob_storage_reader_handle, string $rev_reg_id, string $cred_revoc_id
    ): array;

    public abstract function issuer_merge_revocation_registry_deltas(
        array $rev_reg_delta, array $other_rev_reg_delta
    ): array;

    public abstract function prover_create_master_secret(string $master_secret_name = null): string;

    public abstract function prover_create_credential_req(string $provider_did, array $cred_offer, array $cred_def, string $master_secret_id): string;

    public abstract function prover_set_credential_attr_tag_policy(
        string $cred_def_id, ?array $tag_attrs, bool $retroactive
    );

    public abstract function prover_get_credential_attr_tag_policy(string $cred_def_id): array;

    public abstract function prover_store_credential(
        ?string $cred_id, array $cred_req_metadata, array $cred, array $cred_def, array $rev_reg_def = null
    ): string;

    public abstract function prover_get_credential(string $cred_id): array;

    public abstract function prover_delete_credential(string $cred_id);

    public abstract function prover_get_credentials(array $filters): array;

    public abstract function prover_search_credentials(array $query): array;

    public abstract function prover_get_credentials_for_proof_req(array $proof_request): array;

    public abstract function prover_search_credentials_for_proof_req(
        array $proof_request, array $extra_quest = null, int $limit_referents = 1
    ): array;

    public abstract function prover_create_proof(
        array $proof_req, array $requested_credentials, string $master_secret_name,
        array $schemas, array $credential_defs, array $rev_states
    ): array;

    public abstract function verifier_verify_proof(
        array $proof_request, array $proof, array $schemas, array $credential_defs,
        array $rev_reg_defs, array $rev_regs
    ): bool;

    public abstract function create_revocation_state(
        int $blob_storage_reader_handle, array $rev_reg_def, array $rev_reg_delta,
        int $timestamp, string $cred_rev_id
    ): array;

    public abstract function update_revocation_state(
        int $blob_storage_reader_handle, array $rev_state, array $rev_reg_def,
        array $rev_reg_delta, int $timestamp, string $cred_rev_id
    ): array;

    public abstract function generate_nonce(): string;

    public abstract function to_unqualified(string $entity): string;
}