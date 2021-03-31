<?php


namespace Siruis\Agent\Wallet\Abstracts\Anoncreds;


abstract class AbstractAnonCreds
{
    /**
     * Create credential schema entity that describes credential attributes list and allows credentials
     * interoperability.
     *
     * Schema is public and intended to be shared with all anoncreds workflow actors usually by publishing SCHEMA transaction
     * to Indy distributed ledger.
     *
     * It is IMPORTANT for current version POST Schema in Ledger and after that GET it from Ledger
     * with correct seq_no to save compatibility with Ledger.
     * After that can call indy_issuer_create_and_store_credential_def to build corresponding Credential Definition.
     *
     * @param string $issuer_did DID of schema issuer
     * @param string $name a name the schema
     * @param string $version a version of the schema
     * @param array $attrs a list of schema attributes descriptions (the number of attributes should be less or equal than 125)
     * `["attr1", "attr2"]`
     * @return string|array schema_id: identifier of created schema
     * schema_json: schema as json
     * {
     *      id: identifier of schema
     *      attrNames: array of attribute name strings
     *       name: schema's name string
     *       version: schema's version string,
     *       ver: version of the Schema json
     * }
     */
    public abstract function issuer_create_schema(string $issuer_did, string $name, string $version, array $attrs);

    /**
     * Create credential definition entity that encapsulates credentials issuer DID, credential schema, secrets used for
     * signing credentials and secrets used for credentials revocation.
     *
     * Credential definition entity contains private and public parts. Private part will be stored in the wallet.
     * Public part will be returned as json intended to be shared with all anoncreds workflow actors usually by
     * publishing CRED_DEF transaction to Indy distributed ledger.
     *
     * It is IMPORTANT for current version GET Schema from Ledger with correct seq_no to save compatibility with Ledger.
     *
     * Note: Use combination of `issuer_rotate_credential_def_start` and `issuer_rotate_credential_def_apply` functions
     * to generate new keys for an existing credential definition.
     *
     * @param string $issuer_did a DID of the issuer signing cred_def transaction to the Ledger
     * @param array $schema credential schema as a json
     * {
     *       id: identifier of schema
     *       attrNames: array of attribute name strings
     *       name: schema's name string
     *       version: schema's version string,
     *       seqNo: (Optional) schema's sequence number on the ledger,
     *       ver: version of the Schema json
     * }
     * @param string $tag allows to distinct between credential definitions for the same issuer and schema
     * @param string|null $signature_type credential definition type (optional, 'CL' by default) that defines credentials signature and revocation math.
     * Supported types are:
     *       - 'CL': Camenisch-Lysyanskaya credential signature type that is implemented according to the algorithm in this paper:
     *                   https://github.com/hyperledger/ursa/blob/master/libursa/docs/AnonCred.pdf
     *               And is documented in this HIPE:
     *                   https://github.com/hyperledger/indy-hipe/blob/c761c583b1e01c1e9d3ceda2b03b35336fdc8cc1/text/anoncreds-protocol/README.md
     * @param array|null $config (optional) type-specific configuration of credential definition as json:
     * - 'CL':
     *       {
     *       "support_revocation" - bool (optional, default false) whether to request non-revocation credential
     *       }
     * @return array
     *          cred_def_id: identifier of created credential definition
     *           cred_def_json: public part of created credential definition
     *                {
     *                   id: string - identifier of credential definition
     *                   schemaId: string - identifier of stored in ledger schema
     *                   type: string - type of the credential definition. CL is the only supported type now.
     *                   tag: string - allows to distinct between credential definitions for the same issuer and schema
     *                   value: Dictionary with Credential Definition's data is depended on the signature type: {
     *                       primary: primary credential public key,
     *                       Optional<revocation>: revocation credential public key
     *                   },
     *                   ver: Version of the CredDef json
     *               }
     */
    public abstract function issuer_create_and_store_credential_def(
        string $issuer_did, array $schema, string $tag, string $signature_type = null, array $config = null
    );

    /**
     * Generate temporary credential definitional keys for an existing one (owned by the caller of the library).
     *
     * Use `issuer_rotate_credential_def_apply` function to set generated temporary keys as the main.
     *
     * WARNING: Rotating the credential definitional keys will result in making all credentials issued under the previous keys unverifiable.
     *
     * @param string $cred_def_id an identifier of created credential definition stored in the wallet
     * @param array|null $config (optional) type-specific configuration of credential definition as json:
     * - 'CL':
     *       {
     *          "support_revocation" - bool (optional, default false) whether to request non-revocation credential
     *       }
     * @return array cred_def_json: public part of temporary created credential definition
     */
    public abstract function issuer_rotate_credential_def_start(string $cred_def_id, array $config = null): array;

    /**
     * Apply temporary keys as main for an existing Credential Definition (owned by the caller of the library).
     *
     * WARNING: Rotating the credential definitional keys will result in making all credentials issued under the previous keys unverifiable.
     *
     * @param string $cred_def_id an identifier of created credential definition stored in the wallet
     * @return mixed
     */
    public abstract function issuer_rotate_credential_def_apply(string $cred_def_id);

    /**
     * Create a new revocation registry for the given credential definition as tuple of entities:
     * - Revocation registry definition that encapsulates credentials definition reference, revocation type specific configuration and
     * secrets used for credentials revocation
     * - Revocation registry state that stores the information about revoked entities in a non-disclosing way. The state can be
     * represented as ordered list of revocation registry entries were each entry represents the list of revocation or issuance operations.
     *
     * Revocation registry definition entity contains private and public parts. Private part will be stored in the wallet. Public part
     * will be returned as json intended to be shared with all anoncreds workflow actors usually by publishing REVOC_REG_DEF transaction
     * to Indy distributed ledger.
     *
     * Revocation registry state is stored on the wallet and also intended to be shared as the ordered list of REVOC_REG_ENTRY transactions.
     * This call initializes the state in the wallet and returns the initial entry.
     *
     * Some revocation registry types (for example, 'CL_ACCUM') can require generation of binary blob called tails used to hide information about revoked credentials in public
     * revocation registry and intended to be distributed out of leger (REVOC_REG_DEF transaction will still contain uri and hash of tails).
     * This call requires access to pre-configured blob storage writer instance handle that will allow to write generated tails.
     *
     * @param string $issuer_did a DID of the issuer signing transaction to the Ledger
     * @param string $revoc_def_type revocation registry type (optional, default value depends on credential definition type). Supported types are:
     *                      - 'CL_ACCUM': Type-3 pairing based accumulator implemented according to the algorithm in this paper:
     *                                      https://github.com/hyperledger/ursa/blob/master/libursa/docs/AnonCred.pdf
     *                                    This type is default for 'CL' credential definition type.
     * @param string $tag allows to distinct between revocation registries for the same issuer and credential definition
     * @param string $cred_def_id id of stored in ledger credential definition
     * @param array $config type-specific configuration of revocation registry as json:
     * - 'CL_ACCUM':
     *          "issuance_type": (optional) type of issuance. Currently supported:
     *              1)  ISSUANCE_BY_DEFAULT: all indices are assumed to be issued and initial accumulator is calculated over all indices;
     *                  Revocation Registry is updated only during revocation.
     *              2) ISSUANCE_ON_DEMAND: nothing is issued initially accumulator is 1 (used by default);
     *          "max_cred_num": maximum number of credentials the new registry can process (optional, default 100000)
     * }
     * @param int $tails_writer_handle handle of blob storage to store tails
     *
     * NOTE:
     *      Recursive creation of folder for Default Tails Writer (correspondent to `tails_writer_handle`)
     *      in the system-wide temporary directory may fail in some setup due to permissions: `IO error: Permission denied`.
     *      In this case use `TMPDIR` environment variable to define temporary directory specific for an application.
     *
     * @return mixed
     *      revoc_reg_id: identifier of created revocation registry definition
     *      revoc_reg_def_json: public part of revocation registry definition
     *          {
     *              "id": string - ID of the Revocation Registry,
     *              "revocDefType": string - Revocation Registry type (only CL_ACCUM is supported for now),
     *              "tag": string - Unique descriptive ID of the Registry,
     *              "credDefId": string - ID of the corresponding CredentialDefinition,
     *              "value": Registry-specific data {
     *                  "issuanceType": string - Type of Issuance(ISSUANCE_BY_DEFAULT or ISSUANCE_ON_DEMAND),
     *                  "maxCredNum": number - Maximum number of credentials the Registry can serve.
     *                  "tailsHash": string - Hash of tails.
     *                  "tailsLocation": string - Location of tails file.
     *                  "publicKeys": <public_keys> - Registry's public key (opaque type that contains data structures internal to Ursa.
     *                                                                      It should not be parsed and are likely to change in future versions).
     *              },
     *              "ver": string - version of revocation registry definition json.
     *           }
     *      revoc_reg_entry_json: revocation registry entry that defines initial state of revocation registry
     *          {
     *              value: {
     *                  prevAccum: string - previous accumulator value.
     *                  accum: string - current accumulator value.
     *                  issued: array<number> - an array of issued indices.
     *                  revoked: array<number> an array of revoked indices.
     *              },
     *              ver: string - version revocation registry entry json
     *          }
     */
    public abstract function issuer_create_and_store_revoc_reg(
        string $issuer_did, string $revoc_def_type, string $tag, string $cred_def_id,
        array $config, int $tails_writer_handle
    );

    /**
     * Create credential offer that will be used by Prover for
     * credential request creation. Offer includes nonce and key correctness proof
     * for authentication between protocol steps and integrity checking.
     * @param string $cred_def_id id of credential definition stored in the wallet
     * @return array credential offer json:
     *  {
     *      "schema_id": string, - identifier of schema
     *      "cred_def_id": string, - identifier of credential definition
     *      // Fields below can depend on Cred Def type
     *      "nonce": string,
     *      "key_correctness_proof" : key correctness proof for credential definition correspondent to cred_def_id
     *                                (opaque type that contains data structures internal to Ursa.
     *                                It should not be parsed and are likely to change in future versions).
     * }
     */
    public abstract function issuer_create_credential_offer(string $cred_def_id): array;

    /**
     * Check Cred Request for the given Cred Offer and issue Credential for the given Cred Request.
     *
     * Cred Request must match Cred Offer. The credential definition and revocation registry definition
     * referenced in Cred Offer and Cred Request must be already created and stored into the wallet.
     *
     * Information for this credential revocation will be store in the wallet as part of revocation registry under
     * generated cred_revoc_id local for this wallet.
     *
     * This call returns revoc registry delta as json file intended to be shared as REVOC_REG_ENTRY transaction.
     * Note that it is possible to accumulate deltas to reduce ledger load.
     *
     * @param array $cred_offer a cred offer created by issuer_create_credential_offer
     * @param array $cred_req a credential request created by prover_create_credential_req
     * @param array $cred_values a credential containing attribute values for each of requested attribute names.
     * Example:
     * {
     *      "attr1" : {"raw": "value1", "encoded": "value1_as_int" },
     *      "attr2" : {"raw": "value1", "encoded": "value1_as_int" }
     * }
     * If you want to use empty value for some credential field, you should set "raw" to "" and "encoded" should not be empty
     * @param string|null $rev_reg_id (Optional) id of revocation registry definition stored in the wallet
     * @param int|null $blob_storage_reader_handle pre-configured blob storage reader instance handle that
     * will allow to read revocation tails
     * @return mixed
     * cred_json: Credential json containing signed credential values
     * {
     *      "schema_id": string,
     *      "cred_def_id": string,
     *      "rev_reg_def_id", Optional<string>,
     *      "values": <see cred_values_json above>,
     *      // Fields below can depend on Cred Def type
     *      "signature": <credential signature>,
     *                      (opaque type that contains data structures internal to Ursa.
     *                      It should not be parsed and are likely to change in future versions).
     *      "signature_correctness_proof": credential signature correctness proof
     *                                      (opaque type that contains data structures internal to Ursa.
     *                                      It should not be parsed and are likely to change in future versions).
     *      "rev_reg" - (Optional) revocation registry accumulator value on the issuing moment.
     *                  (opaque type that contains data structures internal to Ursa.
     *                  It should not be parsed and are likely to change in future versions).
     *      "witness" - (Optional) revocation related data
     *                  (opaque type that contains data structures internal to Ursa.
     *                  It should not be parsed and are likely to change in future versions).
     * }
     * cred_revoc_id: local id for revocation info (Can be used for revocation of this cred)
     * revoc_reg_delta_json: Revocation registry delta json with a newly issued credential
     */
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
        array $proof_request, array $extra_query = null, int $limit_referents = 1
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