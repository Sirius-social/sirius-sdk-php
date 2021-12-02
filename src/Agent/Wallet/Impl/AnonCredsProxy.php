<?php


namespace Siruis\Agent\Wallet\Impl;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AbstractAnonCreds;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AnonCredSchema;

class AnonCredsProxy extends AbstractAnonCreds
{
    /**
     * @var \Siruis\Agent\Connections\AgentRPC
     */
    protected $rpc;

    /**
     * AnonCredsProxy constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_schema(string $issuer_did, string $name, string $version, array $attrs): array
    {
        [$schema_id, $body] = $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_create_schema',
            [
                'issuer_did' => $issuer_did,
                'name' => $name,
                'version' => $version,
                'attrs' => $attrs
            ]
        );
        return [$schema_id, new AnonCredSchema($body)];
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_and_store_credential_def(string $issuer_did, array $schema, string $tag, string $signature_type = null, array $config = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_create_and_store_credential_def',
            [
                'issuer_did' => $issuer_did,
                'schema' => $schema,
                'tag' => $tag,
                'signature_type' => $signature_type,
                'config' => $config
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function issuer_rotate_credential_def_start(string $cred_def_id, array $config = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_rotate_credential_def_start',
            [
                'cred_def_id' => $cred_def_id,
                'config' => $config
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function issuer_rotate_credential_def_apply(string $cred_def_id)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_rotate_credential_def_apply',
            ['cred_def_id' => $cred_def_id]
        );
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_and_store_revoc_reg(string $issuer_did, string $revoc_def_type, string $tag, string $cred_def_id, array $config, int $tails_writer_handle)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_create_and_store_revoc_reg',
            [
                'issuer_did' => $issuer_did,
                'revoc_def_type' => $revoc_def_type,
                'tag' => $tag,
                'cred_def_id' => $cred_def_id,
                'config' => $config,
                'tails_writer_handle' => $tails_writer_handle
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_credential_offer(string $cred_def_id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_create_credential_offer',
            ['cred_def_id' => $cred_def_id]
        );
    }

    /**
     * @inheritDoc
     */
    public function issuer_create_credential(array $cred_offer, array $cred_req, array $cred_values, string $rev_reg_id = null, int $blob_storage_reader_handle = null)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_create_credential',
            [
                'cred_offer' => $cred_offer,
                'cred_req' => $cred_req,
                'cred_values' => $cred_values,
                'rev_reg_id' => $rev_reg_id,
                'blob_storage_reader_handle' => $blob_storage_reader_handle
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function issuer_revoke_credential(int $blob_storage_reader_handle, string $rev_reg_id, string $cred_revoc_id): array
    {
        return  $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_revoke_credential',
            [
                'blob_storage_reader_handle' => $blob_storage_reader_handle,
                'rev_reg_id' => $rev_reg_id,
                'cred_revoc_id' => $cred_revoc_id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function issuer_merge_revocation_registry_deltas(array $rev_reg_delta, array $other_rev_reg_delta): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/issuer_merge_revocation_registry_deltas',
            [
                'rev_reg_delta' => $rev_reg_delta,
                'other_rev_reg_delta' => $other_rev_reg_delta
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_create_master_secret(string $master_secret_name = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_create_master_secret',
            ['master_secret_name' => $master_secret_name]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_create_credential_req(string $provider_did, array $cred_offer, array $cred_def, string $master_secret_id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_create_credential_req',
            [
                'prover_did' => $provider_did,
                'cred_offer' => $cred_offer,
                'cred_def' => $cred_def,
                'master_secret_id'
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_set_credential_attr_tag_policy(string $cred_def_id, ?array $tag_attrs, bool $retroactive)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_set_credential_attr_tag_policy',
            [
                'cred_def_id' => $cred_def_id,
                'tag_attrs' => $tag_attrs,
                'retroactive' => $retroactive
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_get_credential_attr_tag_policy(string $cred_def_id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_get_credential_attr_tag_policy',
            [
                'cred_def_id' => $cred_def_id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_store_credential(?string $cred_id, array $cred_req_metadata, array $cred, array $cred_def, array $rev_reg_def = null): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_store_credential',
            [
                'cred_id' => $cred_id,
                'cred_req_metadata' => $cred_req_metadata,
                'cred' => $cred,
                'cred_def' => $cred_def,
                'rev_reg_def' => $rev_reg_def
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_get_credential(string $cred_id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_get_credential',
            [
                'cred_id' => $cred_id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_delete_credential(string $cred_id)
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_delete_credential',
            [
                'cred_id' => $cred_id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_get_credentials(array $filters): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_get_credentials',
            [
                'filters' => $filters
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_search_credentials(array $query): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_search_credentials',
            [
                'query' => $query
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_get_credentials_for_proof_req(array $proof_request): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_get_credentials_for_proof_req',
            [
                'proof_request' => $proof_request
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_search_credentials_for_proof_req(array $proof_request, array $extra_query = null, int $limit_referents = 1): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_search_credentials_for_proof_req',
            [
                'proof_request' => $proof_request,
                'extra_query' => $extra_query,
                'limit_referents' => $limit_referents
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function prover_create_proof(array $proof_req, array $requested_credentials, string $master_secret_name, array $schemas, array $credential_defs, array $rev_states): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/prover_create_proof',
            [
                'proof_req' => $proof_req,
                'requested_credentials' => $requested_credentials,
                'master_secret_name' => $master_secret_name,
                'schemas' => $schemas,
                'credential_defs' => $credential_defs,
                'rev_states' => $rev_states
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function verifier_verify_proof(array $proof_request, array $proof, array $schemas, array $credential_defs, array $rev_reg_defs, array $rev_regs): bool
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/verifier_verify_proof',
            [
                'proof_request' => $proof_request,
                'proof' => $proof,
                'schemas' => $schemas,
                'credential_defs' => $credential_defs,
                'rev_reg_defs' => $rev_reg_defs,
                'rev_regs' => $rev_regs
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function create_revocation_state(int $blob_storage_reader_handle, array $rev_reg_def, array $rev_reg_delta, int $timestamp, string $cred_rev_id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/create_revocation_state',
            [
                'blob_storage_reader_handle' => $blob_storage_reader_handle,
                'rev_reg_def' => $rev_reg_def,
                'rev_reg_delta' => $rev_reg_delta,
                'timestamp' => $timestamp,
                'cred_rev_id' => $cred_rev_id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function update_revocation_state(int $blob_storage_reader_handle, array $rev_state, array $rev_reg_def, array $rev_reg_delta, int $timestamp, string $cred_rev_id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/update_revocation_state',
            [
                'blob_storage_reader_handle' => $blob_storage_reader_handle,
                'rev_state' => $rev_state,
                'rev_reg_def' => $rev_reg_def,
                'rev_reg_delta' => $rev_reg_delta,
                'timestamp' => $timestamp,
                'cred_rev_id' => $cred_rev_id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function generate_nonce(): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/generate_nonce'
        );
    }

    /**
     * @inheritDoc
     */
    public function to_unqualified(string $entity): string
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/to_unqualified',
            [
                'entity' => $entity
            ]
        );
    }
}