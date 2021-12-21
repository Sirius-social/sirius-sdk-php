<?php


namespace Siruis\Hub\Proxies;


use Siruis\Agent\Wallet\Abstracts\Anoncreds\AbstractAnonCreds;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Hub\Core\Hub;

class AnonCredsProxy extends AbstractAnonCreds
{
    /**
     * @var AbstractAnonCreds
     */
    protected $service;

    /**
     * AnonCredsProxy constructor.
     * @throws SiriusInitializationError
     */
    public function __construct()
    {
        $this->service = Hub::current_hub()->get_anoncreds();
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_schema(string $issuer_did, string $name, string $version, array $attrs)
    {
        return $this->service->issuer_create_schema($issuer_did, $name, $version, $attrs);
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_and_store_credential_def(string $issuer_did, array $schema, string $tag, string $signature_type = null, array $config = null): array
    {
        return $this->service->issuer_create_and_store_credential_def($issuer_did, $schema, $tag, $signature_type, $config);
    }

    /**
     * @inheritDoc
     */
    public function issuer_rotate_credential_def_start(string $cred_def_id, array $config = null): array
    {
        return $this->service->issuer_rotate_credential_def_start($cred_def_id, $config);
    }

    /**
     * @inheritDoc
     */
    public function issuer_rotate_credential_def_apply(string $cred_def_id)
    {
        return $this->service->issuer_rotate_credential_def_apply($cred_def_id);
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_and_store_revoc_reg(string $issuer_did, string $revoc_def_type, string $tag, string $cred_def_id, array $config, int $tails_writer_handle)
    {
        return $this->service->issuer_create_and_store_revoc_reg($issuer_did, $revoc_def_type, $tag, $cred_def_id, $config, $tails_writer_handle);
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_credential_offer(string $cred_def_id): array
    {
        return $this->service->issuer_create_credential_offer($cred_def_id);
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_credential(array $cred_offer, array $cred_req, array $cred_values, string $rev_reg_id = null, int $blob_storage_reader_handle = null)
    {
        return $this->service->issuer_create_credential($cred_offer, $cred_req, $cred_values, $rev_reg_id, $blob_storage_reader_handle);
    }

    public function issuer_revoke_credential(int $blob_storage_reader_handle, string $rev_reg_id, string $cred_revoc_id): array
    {
        return  $this->service->issuer_revoke_credential($blob_storage_reader_handle, $rev_reg_id, $cred_revoc_id);
    }

    public function issuer_merge_revocation_registry_deltas(array $rev_reg_delta, array $other_rev_reg_delta): array
    {
        return $this->service->issuer_merge_revocation_registry_deltas($rev_reg_delta, $other_rev_reg_delta);
    }

    public function prover_create_master_secret(string $master_secret_name = null): string
    {
        return $this->service->prover_create_master_secret($master_secret_name);
    }

    public function prover_create_credential_req(string $provider_did, array $cred_offer, array $cred_def, string $master_secret_id): array
    {
        return $this->service->prover_create_credential_req($provider_did, $cred_offer, $cred_def, $master_secret_id);
    }

    public function prover_set_credential_attr_tag_policy(string $cred_def_id, ?array $tag_attrs, bool $retroactive)
    {
        return $this->service->prover_set_credential_attr_tag_policy($cred_def_id, $tag_attrs, $retroactive);
    }

    public function prover_get_credential_attr_tag_policy(string $cred_def_id): array
    {
        return $this->service->prover_get_credential_attr_tag_policy($cred_def_id);
    }

    public function prover_store_credential(?string $cred_id, array $cred_req_metadata, array $cred, array $cred_def, array $rev_reg_def = null): string
    {
        return $this->service->prover_store_credential($cred_id, $cred_req_metadata, $cred, $cred_def, $rev_reg_def);
    }

    public function prover_get_credential(string $cred_id): array
    {
        return $this->service->prover_get_credential($cred_id);
    }

    public function prover_delete_credential(string $cred_id)
    {
        return $this->service->prover_delete_credential($cred_id);
    }

    public function prover_get_credentials(array $filters): array
    {
        return $this->service->prover_get_credentials($filters);
    }

    public function prover_search_credentials(array $query): array
    {
        return $this->service->prover_search_credentials($query);
    }

    public function prover_get_credentials_for_proof_req(array $proof_request): array
    {
        return $this->service->prover_get_credentials_for_proof_req($proof_request);
    }

    public function prover_search_credentials_for_proof_req(array $proof_request, array $extra_query = null, int $limit_referents = 1): array
    {
        return $this->service->prover_search_credentials_for_proof_req($proof_request, $extra_query, $limit_referents);
    }

    public function prover_create_proof(array $proof_req, array $requested_credentials, string $master_secret_name, array $schemas, array $credential_defs, array $rev_states): array
    {
        return $this->service->prover_create_proof($proof_req, $requested_credentials, $master_secret_name, $schemas, $credential_defs, $rev_states);
    }

    public function verifier_verify_proof(array $proof_request, array $proof, array $schemas, array $credential_defs, array $rev_reg_defs, array $rev_regs): bool
    {
        return $this->service->verifier_verify_proof($proof_request, $proof, $schemas, $credential_defs, $rev_reg_defs, $rev_regs);
    }

    public function create_revocation_state(int $blob_storage_reader_handle, array $rev_reg_def, array $rev_reg_delta, int $timestamp, string $cred_rev_id): array
    {
        return $this->service->create_revocation_state($blob_storage_reader_handle, $rev_reg_def, $rev_reg_delta, $timestamp, $cred_rev_id);
    }

    public function update_revocation_state(int $blob_storage_reader_handle, array $rev_state, array $rev_reg_def, array $rev_reg_delta, int $timestamp, string $cred_rev_id): array
    {
        return $this->service->update_revocation_state($blob_storage_reader_handle, $rev_state, $rev_reg_def, $rev_reg_delta, $timestamp, $cred_rev_id);
    }

    public function generate_nonce(): string
    {
        return $this->service->generate_nonce();
    }

    public function to_unqualified(string $entity): string
    {
        return $this->service->to_unqualified($entity);
    }
}