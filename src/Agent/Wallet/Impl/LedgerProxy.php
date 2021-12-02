<?php


namespace Siruis\Agent\Wallet\Impl;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Wallet\Abstracts\Ledger\AbstractLedger;
use Siruis\Agent\Wallet\Abstracts\Ledger\NYMRole;

class LedgerProxy extends AbstractLedger
{
    /**
     * @var \Siruis\Agent\Connections\AgentRPC
     */
    private $rpc;

    /**
     * LedgerProxy constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->rpc = $rpc;
    }

    /**
     * @inheritDoc
     */
    public function read_nym(string $pool_name, ?string $submitter_did, string $target_did): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/read_nym',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'target_did' => $target_did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function read_attribute(string $pool_name, ?string $submitter_did, string $target_did, string $name): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/read_attribute',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'target_did' => $target_did,
                'name' => $name
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function write_nym(string $pool_name, string $submitter_did, string $target_did, string $ver_key = null, string $alias = null, $role = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/write_nym',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'target_did' => $target_did,
                'ver_key' => $ver_key,
                'alias' => $alias,
                'role' => $role
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function register_schema(string $pool_name, string $submitter_did, array $data): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/register_schema',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'data' => $data
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function register_cred_def(string $pool_name, string $submitter_did, array $data): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/register_cred_def',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'data' => $data
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function write_attribute(string $pool_name, ?string $submitter_did, string $target_did, string $name, $value): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/write_attribute',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'target_did' => $target_did,
                'name' => $name,
                'value' => $value
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function sign_and_submit_request(string $pool_name, string $submitter_did, array $request): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/sign_and_submit_request',
            [
                'pool_name' => $pool_name,
                'submitter_did' => $submitter_did,
                'request' => $request
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function submit_request(string $pool_name, array $request, array $nodes = null, int $timeout = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/submit_request',
            [
                'pool_name' => $pool_name,
                'request' => $request
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function submit_action(string $pool_name, array $request, array $nodes = null, int $timeout = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/submit_action',
            [
                'pool_name' => $pool_name,
                'request' => $request,
                'nodes' => $nodes,
                'timeout' => $timeout
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function sign_request(string $submitter_did, array $request): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/sign_request',
            [
                'submitter_did' => $submitter_did,
                'request' => $request
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function multi_sign_request(string $submitter_did, array $request): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/multi_sign_request',
            [
                'submitter_did' => $submitter_did,
                'request' => $request
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_ddo_request(?string $submitter_did, string $target_did): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_ddo_request',
            [
                'submitter_did' => $submitter_did,
                'target_did' => $target_did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_nym_request(string $submitter_did, string $target_did, string $ver_key = null, string $alias = null, NYMRole $role = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_nym_request',
            [
                'submitter_did' => $submitter_did,
                'target_did' => $target_did,
                'ver_key' => $ver_key,
                'alias' => $alias,
                'role' => $role
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_attrib_request(string $submitter_did, string $target_did, string $xhash = null, array $raw = null, string $enc = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_attrib_request',
            [
                'submitter_did' => $submitter_did,
                'target_did' => $target_did,
                'xhash' => $xhash,
                'raw' => $raw,
                'enc' => $enc
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_attrib_request(?string $submitter_did, string $target_did, string $raw = null, string $xhash = null, string $enc = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_attrib_request',
            [
                'submitter_did' => $submitter_did,
                'target_did' => $target_did,
                'xhash' => $xhash,
                'raw' => $raw,
                'enc' => $enc
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_nym_request(?string $submitter_did, string $target_did): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_nym_request',
            [
                'submitter_did' => $submitter_did,
                'target_did' => $target_did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function parse_get_nym_response($response): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/parse_get_nym_response',
            [
                'response' => $response,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_schema_request(string $submitter_did, array $data): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_schema_request',
            [
                'submitter_did' => $submitter_did,
                'data' => $data
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_schema_request(?string $submitter_did, string $id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_schema_request',
            [
                'submitter_did' => $submitter_did,
                'id_' => $id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function parse_get_schema_response(array $get_schema_response): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/parse_get_schema_response',
            [
                'get_schema_response' => $get_schema_response,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_cred_def_request(string $submitter_did, array $data): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_cred_def_request',
            [
                'submitter_did' => $submitter_did,
                'data' => $data
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_cred_def_request(?string $submitter_did, string $id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_cred_def_request',
            [
                'submitter_did' => $submitter_did,
                'id_' => $id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function parse_get_cred_def_response(array $get_cred_def_response): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/parse_get_cred_def_response',
            [
                'get_cred_def_response' => $get_cred_def_response,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_node_request(string $submitter_did, string $target_did, array $data): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_node_request',
            [
                'submitter_did' => $submitter_did,
                'target_did' => $target_did,
                'data' => $data
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_validator_info_request(string $submitter_did): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_validator_info_request',
            [
                'submitter_did' => $submitter_did,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_txn_request(?string $submitter_did, ?string $ledger_type, int $seq_no): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_txn_request',
            [
                'submitter_did' => $submitter_did,
                'ledger_type' => $ledger_type,
                'seq_no' => $seq_no
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_pool_config_request(string $submitter_did, bool $writes, bool $force): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_pool_config_request',
            [
                'submitter_did' => $submitter_did,
                'writes' => $writes,
                'force' => $force
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_pool_restart_request(string $submitter_did, string $action, string $datetime): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_pool_restart_request',
            [
                'submitter_did' => $submitter_did,
                'action' => $action,
                'datetime' => $datetime
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_pool_upgrade_request(string $submitter_did, string $name, string $version, string $action, string $_sha256, ?int $_timeout, ?string $schedule, ?string $justification, bool $reinstall, bool $force, ?string $package): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_pool_upgrade_request',
            [
                'submitter_did' => $submitter_did,
                'name' => $name,
                'version' => $version,
                'action' => $action,
                '_sha256' => $_sha256,
                '_timeout' => $_timeout,
                'schedule' => $schedule,
                'justification' => $justification,
                'reinstall' => $reinstall,
                'force' => $force,
                'package' => $package
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_revoc_reg_def_request(string $submitter_did, string $data): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_revoc_reg_def_request',
            [
                'submitter_did' => $submitter_did,
                'data' => $data
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_revoc_reg_def_request(?string $submitter_did, string $rev_reg_def_id): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_revoc_reg_def_request',
            [
                'submitter_did' => $submitter_did,
                'rev_reg_def_id' => $rev_reg_def_id
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function parse_get_revoc_reg_def_response(array $get_revoc_ref_def_response): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/parse_get_revoc_reg_def_response',
            [
                'get_revoc_ref_def_response' => $get_revoc_ref_def_response,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_revoc_reg_entry_request(string $submitter_did, string $revoc_reg_def_id, string $rev_def_type, array $value): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_revoc_reg_entry_request',
            [
                'submitter_did' => $submitter_did,
                'revoc_reg_def_id' => $revoc_reg_def_id,
                'rev_def_type' => $rev_def_type,
                'value' => $value
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_revoc_reg_request(?string $submitter_did, string $revoc_reg_def_id, int $timestamp): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_revoc_reg_delta_request',
            [
                'submitter_did' => $submitter_did,
                'revoc_reg_def_id' => $revoc_reg_def_id,
                'timestamp' => $timestamp
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function parse_get_revoc_reg_response(array $get_revoc_reg_response): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/parse_get_revoc_reg_def_response',
            [
                'get_revoc_reg_response' => $get_revoc_reg_response,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_revoc_reg_delta_request(array $get_revoc_reg_delta_response): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/parse_get_revoc_reg_delta_response',
            [
                'get_revoc_reg_delta_response' => $get_revoc_reg_delta_response,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function get_response_metadata(array $response): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/get_response_metadata',
            [
                'response' => $response,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_auth_rule_request(string $submitter_did, string $txn_type, string $action, string $field, ?string $old_value, ?string $new_value, array $constraint): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_auth_rule_request',
            [
                'submitter_did' => $submitter_did,
                'txn_type' => $txn_type,
                'action' => $action,
                'field' => $field,
                'old_value' => $old_value,
                'new_value' => $new_value,
                'constraint' => $constraint
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_auth_rules_request(string $submitter_did, array $data): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_auth_rules_request',
            [
                'submitter_did' => $submitter_did,
                'data' => $data
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_auth_rule_request(?string $submitter_did, ?string $txn_type, ?string $action, ?string $field, ?string $old_value, ?string $new_value): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_auth_rules_request',
            [
                'submitter_did' => $submitter_did,
                'txn_type' => $txn_type,
                'action' => $action,
                'field' => $field,
                'old_value' => $old_value,
                'new_value' => $new_value
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_txn_author_agreement_request(string $submitter_did, ?string $text, string $version, int $ratification_ts = null, int $retirement_ts = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_txn_author_agreement_request',
            [
                'submitter_did' => $submitter_did,
                'text' => $text,
                'version' => $version,
                'ratification_ts' => $ratification_ts,
                'retirement_ts' => $retirement_ts
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_disable_all_txn_author_agreements_request(string $submitter_did): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_disable_all_txn_author_agreements_request',
            [
                'submitter_did' => $submitter_did
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_txn_author_agreement_request(?string $submitter_did, array $data = null): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_txn_author_agreement_request',
            [
                'submitter_did' => $submitter_did,
                'data' => $data
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_acceptance_mechanisms_request(string $submitter_did, array $aml, string $version, ?string $aml_context): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_acceptance_mechanisms_request',
            [
                'submitter_did' => $submitter_did,
                'aml' => $aml,
                'version' => $version,
                'aml_context' => $aml_context
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function build_get_acceptance_mechanisms_request(?string $submitter_did, ?int $timestamp, ?string $version): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/build_get_acceptance_mechanisms_request',
            [
                'submitter_did' => $submitter_did,
                'timestamp' => $timestamp,
                'version' => $version,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function append_txn_author_agreement_acceptance_to_request(array $request, ?string $text, ?string $version, ?string $taa_digest, string $mechanism, int $time): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/append_txn_author_agreement_acceptance_to_request',
            [
                'request' => $request,
                'text' => $text,
                'version' => $version,
                'taa_digest' => $taa_digest,
                'mechanism' => $mechanism,
                'time' => $time
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function append_request_endorser(array $request, string $endorser_did): array
    {
        return $this->rpc->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/append_request_endorser',
            [
                'request' => $request,
                'endorser_did' => $endorser_did,
            ]
        );
    }
}