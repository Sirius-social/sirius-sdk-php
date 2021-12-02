<?php


namespace Siruis\Agent\Wallet\Abstracts\Ledger;


abstract class AbstractLedger
{
    /**
     * Builds a GET_NYM request. Request to get information about a DID (NYM).
     *
     * @param string $pool_name Ledger pool.
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $target_did Target DID as base58-encoded string for 16 or 32 bit DID value.
     * @return array result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function read_nym(string $pool_name, ?string $submitter_did, string $target_did): array;

    /**
     * Builds a GET_ATTRIB request. Request to get information about an Attribute for the specified DID.
     *
     * @param string $pool_name Ledger
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $target_did Target DID as base58-encoded string for 16 or 32 bit DID value.
     * @param string $name attribute name.
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function read_attribute(string $pool_name, ?string $submitter_did, string $target_did, string $name): array;

    /**
     * Builds a NYM request.
     *
     * @param string $pool_name Ledger pool.
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $target_did Target DID as base58-encoded string for 16 or 32 bit DID value.
     * @param string|null $ver_key Target identity verification key as base58-encoded string.
     * @param string|null $alias NYM's alias.
     * @param NYMRole|array|null $role Role of a user NYM record:
     *                           null (common USER)
     *                           TRUSTEE
     *                           STEWARD
     *                           TRUST_ANCHOR
     *                           ENDORSER - equal to TRUST_ANCHOR that will be removed soon
     *                           NETWORK_MONITOR
     *                           empty string to reset role
     * @return array success, result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function write_nym(
        string $pool_name, string $submitter_did, string $target_did,
        string $ver_key = null, string $alias = null, $role = null
    ): array;

    /**
     * Builds a SCHEMA request. Request to add Credential's schema.
     *
     * @param string $pool_name Ledger
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param array $data Schema data
     * {
     *      id: identifier of schema
     *      attrNames: array of attribute name strings
     *      name: schema's name string
     *      version: schema's version string,
     *      ver: version of the Schema json
     * }
     * @return array success, Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function register_schema(string $pool_name, string $submitter_did, array $data): array;

    /**
     * Builds an CRED_DEF request. Request to add a credential definition (in particular, public key),
     * that Issuer creates for a particular Credential Schema.
     *
     * @param string $pool_name Ledger
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param array $data credential definition json
     *                      {
     *                          id: string - identifier of credential definition
     *                          schemaId: string - identifier of stored in ledger schema
     *                          type: string - type of the credential definition. CL is the only supported type now.
     *                          tag: string - allows to distinct between credential definitions for the same issuer and schema
     *                          value: Dictionary with Credential Definition's data: {
     *                              primary: primary credential public key,
     *                              Optional<revocation>: revocation credential public key
     *                          },
     *                          ver: Version of the CredDef json
     *                      }
     * @return array success, Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function register_cred_def(string $pool_name, string $submitter_did, array $data): array;

    /**
     * Builds an ATTRIB request. Request to add attribute to a NYM record.
     *
     * @param string $pool_name Ledger
     * @param string|null $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                                   Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $target_did Target DID as base58-encoded string for 16 or 32 bit DID value.
     * @param string $name attribute name
     * @param mixed $value attribute value
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function write_attribute(
        string $pool_name, ?string $submitter_did, string $target_did, string $name, $value
    ): array;

    /**
     * Signs and submits request message to validator pool.
     *
     * Adds submitter information to passed request json, signs it with submitter
     * sign key (see wallet_sign), and sends signed request message
     * to validator pool (see write_request).
     *
     * @param string $pool_name Ledger pool.
     * @param string $submitter_did Id of Identity stored in secured Wallet.
     * @param array $request Request data json.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function sign_and_submit_request(string $pool_name, string $submitter_did, array $request): array;

    /**
     * Send action to particular nodes of validator pool.
     *
     * The list of requests can be send:
     * POOL_RESTART
     * GET_VALIDATOR_INFO
     *
     * The request is sent to the nodes as is. It's assumed that it's already prepared.
     *
     * @param string $pool_name Ledger pool.
     * @param array $request Request data json.
     * @param array|null $nodes (Optional) List of node names to send the request.
     *                            ["Node1", "Node2", .... "NodeN"]
     * @param int|null $timeout (Optional) Time to wait respond from nodes (override the default timeout) (in sec).
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function submit_request(
        string $pool_name, array $request, array $nodes = null, int $timeout = null
    ): array;

    /**
     * Send action to particular nodes of validator pool.
     *
     * The list of requests can be send:
     * POOL_RESTART
     * GET_VALIDATOR_INFO
     *
     * The request is sent to the nodes as is. It's assumed that it's already prepared.
     *
     * @param string $pool_name Ledger pool.
     * @param array $request Request data json.
     * @param array|null $nodes (Optional) List of node names to send the request.
     *                          ["Node1", "Node2",...."NodeN"]
     * @param int|null $timeout (Optional) Time to wait respond from nodes (override the default timeout) (in sec).
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function submit_action(string $pool_name, array $request, array $nodes = null, int $timeout = null): array;

    /**
     * Signs request message.
     *
     * Adds submitter information to passed request json, signs it with submitter
     * sign key (see wallet_sign).
     *
     * @param string $submitter_did Id of Identity stored in secured Wallet.
     * @param array $request Request data json.
     * @return array Signed request json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function sign_request(string $submitter_did, array $request): array;

    /**
     * Multi signs request message.
     *
     * Adds submitter information to passed request json, signs it with submitter
     * sign key (see wallet_sign).
     *
     * @param string $submitter_did Id of Identity stored in secured Wallet.
     * @param array $request Request data json.
     * @return array Signed request json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function multi_sign_request(string $submitter_did, array $request): array;

    /**
     * Builds a request to get a DDO.
     *
     * @param string|null $submitter_did  (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $target_did Id of Identity stored in secured Wallet.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_ddo_request(?string $submitter_did, string $target_did): array;

    /**
     * Builds a NYM request.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $target_did Target DID as base58-encoded string for 16 or 32 bit DID value.
     * @param string|null $ver_key Target identity verification key as base58-encoded string.
     * @param string|null $alias NYM's alias.
     * @param NYMRole|null $role Role of a user NYM record:
     *                                          null (common USER)
     *                                          TRUSTEE
     *                                          STEWARD
     *                                          TRUST_ANCHOR
     *                                          ENDORSER - equal to TRUST_ANCHOR that will be removed soon
     *                                          NETWORK_MONITOR
     *                                          empty string to reset role
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_nym_request(
        string $submitter_did, string $target_did, string $ver_key = null,
        string $alias = null, NYMRole $role = null
    ): array;

    /**
     * Builds an ATTRIB request. Request to add attribute to a NYM record.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $target_did Target DID as base58-encoded string for 16 or 32 bit DID value.
     * @param string|null $xhash (Optional) Hash of attribute data.
     * @param array|null $raw (Optional) Json, where key is attribute name and value is attribute value.
     * @param string|null $enc (Optional) Encrypted value attribute data.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_attrib_request(
        string $submitter_did, string $target_did, string $xhash = null,
        array $raw = null, string $enc = null
    ): array;

    /**
     * Builds a GET_ATTRIB request. Request to get information about an Attribute for the specified DID.
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $target_did Target DID as base58-encoded string for 16 or 32 bit DID value.
     * @param string|null $raw (Optional) Requested attribute name.
     * @param string|null $xhash (Optional) Requested attribute hash.
     * @param string|null $enc (Optional) Requested attribute encrypted value.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_attrib_request(
        ?string $submitter_did, string $target_did, string $raw = null,
        string $xhash = null, string $enc = null
    ): array;

    /**
     * Builds a GET_NYM request. Request to get information about a DID (NYM).
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $target_did Target DID as base58-encoded string for 16 or 32 bit DID value.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_nym_request(
        ?string $submitter_did, string $target_did
    ): array;

    /**
     * Parse a GET_NYM response to get NYM data.
     *
     * @param mixed $response response on GET_NYM request.
     * @return array NYM data
     * {
     *      did: DID as base58-encoded string for 16 or 32 bit DID value.
     *      verkey: verification key as base58-encoded string.
     *      role: Role associated number
     *                              null (common USER)
     *                              0 - TRUSTEE
     *                              2 - STEWARD
     *                              101 - TRUST_ANCHOR
     *                              101 - ENDORSER - equal to TRUST_ANCHOR that will be removed soon
     *                              201 - NETWORK_MONITOR
     * }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function parse_get_nym_response($response): array;

    /**
     * Builds a SCHEMA request. Request to add Credential's schema.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     * Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param array $data Credential schema.
     *                      {
     *                          id: identifier of schema
     *                          attrNames: array of attribute name strings (the number of attributes should be less or equal than 125)
     *                          name: Schema's name string
     *                          version: Schema's version string,
     *                          ver: Version of the Schema json
     *                      }
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_schema_request(string $submitter_did, array $data): array;

    /**
     * Builds a GET_SCHEMA request. Request to get Credential's Schema.
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $id Schema Id in ledger
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_schema_request(?string $submitter_did, string $id): array;

    /**
     * Parse a GET_SCHEMA response to get Schema in the format compatible with Anoncreds API
     *
     * @param array $get_schema_response response of GET_SCHEMA request.
     * @return array Schema Id and Schema json.
     *  {
     *      id: identifier of schema
     *      attrNames: array of attribute name strings
     *      name: Schema's name string
     *      version: Schema's version string
     *      ver: Version of the Schema json
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function parse_get_schema_response(array $get_schema_response): array;

    /**
     * Builds an CRED_DEF request. Request to add a credential definition (in particular, public key),
     * that Issuer creates for a particular Credential Schema.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param array $data credential definition json
     *                      {
     *                          id: string - identifier of credential definition
     *                          schemaId: string - identifier of stored in ledger schema
     *                          type: string - type of the credential definition. CL is the only supported type now.
     *                          tag: string - allows to distinct between credential definitions for the same issuer and schema
     *                          value: Dictionary with Credential Definition's data: {
     *                              primary: primary credential public key,
     *                              Optional<revocation>: revocation credential public key
     *                          },
     *                          ver: Version of the CredDef json
     *                      }
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_cred_def_request(string $submitter_did, array $data): array;

    /**
     * Builds a GET_CRED_DEF request. Request to get a credential definition (in particular, public key),
     * that Issuer creates for a particular Credential Schema.
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $id Credential Definition Id in ledger.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_cred_def_request(?string $submitter_did, string $id): array;

    /**
     * Parse a GET_CRED_DEF response to get Credential Definition in the format compatible with Anoncreds API.
     *
     * @param array $get_cred_def_response get_cred_def_response: response of GET_CRED_DEF request.
     * @return array Credential Definition Id and Credential Definition json.
     *  {
     *      id: string - identifier of credential definition
     *      schemaId: string - identifier of stored in ledger schema
     *      type: string - type of the credential definition. CL is the only supported type now.
     *      tag: string - allows to distinct between credential definitions for the same issuer and schema
     *      value: Dictionary with Credential Definition's data: {
     *          primary: primary credential public key,
     *          Optional<revocation>: revocation credential public key
     *      },
     *      ver: Version of the Credential Definition json
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function parse_get_cred_def_response(array $get_cred_def_response): array;

    /**
     * Builds a NODE request. Request to add a new node to the pool, or updates existing in the pool.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     * Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $target_did Target Node's DID.  It differs from submitter_did field.
     * @param array $data Data associated with the Node:
     *  {
     *      alias: string - Node's alias
     *      blskey: string - (Optional) BLS multi-signature key as base58-encoded string.
     *      blskey_pop: string - (Optional) BLS key proof of possession as base58-encoded string.
     *      client_ip: string - (Optional) Node's client listener IP address.
     *      client_port: string - (Optional) Node's client listener port.
     *      node_ip: string - (Optional) The IP address other Nodes use to communicate with this Node.
     *      node_port: string - (Optional) The port other Nodes use to communicate with this Node.
     *      services: array<string> - (Optional) The service of the Node. VALIDATOR is the only supported one now.
     *  }
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_node_request(string $submitter_did, string $target_did, array $data): array;

    /**
     * Builds a GET_VALIDATOR_INFO request.
     *
     * @param string $submitter_did Id of Identity stored in secured Wallet.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_validator_info_request(string $submitter_did): array;

    /**
    Builds a GET_TXN request. Request to get any transaction by its seq_no.
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string|null $ledger_type (Optional) type of the ledger the requested transaction belongs to:
     *      DOMAIN - used default,
     *      POOL,
     *      CONFIG
     *      any number
     * @param int $seq_no requested transaction sequence number as it's stored on Ledger.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_txn_request(?string $submitter_did, ?string $ledger_type, int $seq_no): array;

    /**
     * Builds a POOL_CONFIG request. Request to change Pool's configuration.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param bool $writes Whether any write requests can be processed by the pool
     *                      (if false, then pool goes to read-only state). True by default.
     * @param bool $force Whether we should apply transaction (for example, move pool to read-only state)
     *                      without waiting for consensus of this transaction
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_pool_config_request(string $submitter_did, bool $writes, bool $force): array;

    /**
     * Builds a POOL_RESTART request
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $action Action that pool has to do after received transaction.
     *                       Can be "start" or "cancel"
     * @param string $datetime Time when pool must be restarted.
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_pool_restart_request(string $submitter_did, string $action, string $datetime): array;

    /**
     * Builds a POOL_UPGRADE request. Request to upgrade the Pool (sent by Trustee).
     * It upgrades the specified Nodes (either all nodes in the Pool, or some specific ones).
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $name Human-readable name for the upgrade.
     * @param string $version The version of indy-node package we perform upgrade to.
     *                        Must be greater than existing one (or equal if reinstall flag is True).
     * @param string $action Either start or cancel.
     * @param string $_sha256 sha256 hash of the package.
     * @param int|null $_timeout (Optional) Limits upgrade time on each Node.
     * @param string|null $schedule (Optional) Schedule of when to perform upgrade on each node. Map Node DIDs to upgrade time.
     * @param string|null $justification (Optional) justification string for this particular Upgrade.
     * @param bool $reinstall Whether it's allowed to re-install the same version. False by default.
     * @param bool $force Whether we should apply transaction (schedule Upgrade) without waiting
     *                    for consensus of this transaction.
     * @param string|null $package (Optional) Package to be upgraded.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_pool_upgrade_request(
        string $submitter_did, string $name, string $version, string $action, string $_sha256, ?int $_timeout,
        ?string $schedule, ?string $justification, bool $reinstall, bool $force, ?string $package
    ): array;

    /**
     * Builds a REVOC_REG_DEF request. Request to add the definition of revocation registry
     * to an exists credential definition.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $data Revocation Registry data:
     *  {
     *      "id": string - ID of the Revocation Registry,
     *      "revocDefType": string - Revocation Registry type (only CL_ACCUM is supported for now),
     *      "tag": string - Unique descriptive ID of the Registry,
     *      "credDefId": string - ID of the corresponding CredentialDefinition,
     *      "value": Registry-specific data {
     *          "issuanceType": string - Type of Issuance(ISSUANCE_BY_DEFAULT or ISSUANCE_ON_DEMAND),
     *          "maxCredNum": number - Maximum number of credentials the Registry can serve.
     *          "tailsHash": string - Hash of tails.
     *          "tailsLocation": string - Location of tails file.
     *          "publicKeys": <public_keys> - Registry's public key.
     *      },
     *      "ver": string - version of revocation registry definition json.
     *  }
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_revoc_reg_def_request(string $submitter_did, string $data): array;

    /**
     * Builds a GET_REVOC_REG_DEF request. Request to get a revocation registry definition,
     * that Issuer creates for a particular Credential Definition.
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $rev_reg_def_id ID of Revocation Registry Definition in ledger.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_revoc_reg_def_request(?string $submitter_did, string $rev_reg_def_id): array;

    /**
     * Parse a GET_REVOC_REG_DEF response to get Revocation Registry Definition in the format compatible with Anoncreds API.
     *
     * @param array $get_revoc_ref_def_response response of GET_REVOC_REG_DEF request.
     * @return array Revocation Registry Definition Id and Revocation Registry Definition json.
     *  {
     *      "id": string - ID of the Revocation Registry,
     *      "revocDefType": string - Revocation Registry type (only CL_ACCUM is supported for now),
     *      "tag": string - Unique descriptive ID of the Registry,
     *      "credDefId": string - ID of the corresponding CredentialDefinition,
     *      "value": Registry-specific data {
     *          "issuanceType": string - Type of Issuance(ISSUANCE_BY_DEFAULT or ISSUANCE_ON_DEMAND),
     *          "maxCredNum": number - Maximum number of credentials the Registry can serve.
     *          "tailsHash": string - Hash of tails.
     *          "tailsLocation": string - Location of tails file.
     *          "publicKeys": <public_keys> - Registry's public key.
     *      },
     *      "ver": string - version of revocation registry definition json.
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function parse_get_revoc_reg_def_response(array $get_revoc_ref_def_response): array;

    /**
     * Builds a REVOC_REG_ENTRY request.  Request to add the RevocReg entry containing
     * the new accumulator value and issued/revoked indices.
     * This is just a delta of indices, not the whole list. So, it can be sent each time a new credential is issued/revoked.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string $revoc_reg_def_id  ID of the corresponding RevocRegDef.
     * @param string $rev_def_type  Revocation Registry type (only CL_ACCUM is supported for now).
     * @param array $value Registry-specific data:
     *  {
     *      value: {
     *          prevAccum: string - previous accumulator value.
     *          accum: string - current accumulator value.
     *          issued: array<number> - an array of issued indices.
     *          revoked: array<number> an array of revoked indices.
     *      },
     *      ver: string - version revocation registry entry json
     *  }
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_revoc_reg_entry_request(
        string $submitter_did, string $revoc_reg_def_id, string $rev_def_type, array $value
    ): array;

    /**
     * Builds a GET_REVOC_REG request. Request to get the accumulated state of the Revocation Registry
     * by ID. The state is defined by the given timestamp.
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string $revoc_reg_def_id ID of the corresponding Revocation Registry Definition in ledger.
     * @param int $timestamp Requested time represented as a total number of seconds from Unix Epoch
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_revoc_reg_request(?string $submitter_did, string $revoc_reg_def_id, int $timestamp): array;

    /**
     * Parse a GET_REVOC_REG response to get Revocation Registry in the format compatible with Anoncreds API.
     *
     * @param array $get_revoc_reg_response response of GET_REVOC_REG request.
     * @return array Revocation Registry Definition Id, Revocation Registry json and Timestamp.
     *  {
     *      "value": Registry-specific data {
     *          "accum": string - current accumulator value.
     *      },
     *      "ver": string - version revocation registry json
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function parse_get_revoc_reg_response(array $get_revoc_reg_response): array;

    /**
     * Parse a GET_REVOC_REG_DELTA response to get Revocation Registry Delta in the format compatible with Anoncreds API.
     *
     * @param array $get_revoc_reg_delta_response response of GET_REVOC_REG_DELTA request.
     * @return array Revocation Registry Definition Id, Revocation Registry Delta json and Timestamp.
     *  {
     *      "value": Registry-specific data {
     *          prevAccum: string - previous accumulator value.
     *          accum: string - current accumulator value.
     *          issued: array<number> - an array of issued indices.
     *          revoked: array<number> an array of revoked indices.
     *      },
     *      "ver": string
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_revoc_reg_delta_request(array $get_revoc_reg_delta_response): array;

    /**
     * Parse transaction response to fetch metadata.
     * The important use case for this method is validation of Node's response freshens.
     *
     * Distributed Ledgers can reply with outdated information for consequence read request after write.
     * To reduce pool load libindy sends read requests to one random node in the pool.
     * Consensus validation is performed based on validation of nodes multi signature for current ledger Merkle Trie root.
     * This multi signature contains information about the latest ldeger's transaction ordering time and sequence number that this method returns.
     *
     * If node that returned response for some reason is out of consensus and has outdated ledger
     * it can be caught by analysis of the returned latest ledger's transaction ordering time and sequence number.
     *
     * There are two ways to filter outdated responses:
     * 1) based on "seqNo" - sender knows the sequence number of transaction that he consider as a fresh enough.
     * 2) based on "txnTime" - sender knows the timestamp that he consider as a fresh enough.
     *
     * Note: response of GET_VALIDATOR_INFO request isn't supported
     *
     * @param array $response response of write or get request.
     * @return array Response Metadata.
     *  {
     *      "seqNo": Option<u64> - transaction sequence number,
     *      "txnTime": Option<u64> - transaction ordering time,
     *      "lastSeqNo": Option<u64> - the latest transaction seqNo for particular Node,
     *      "lastTxnTime": Option<u64> - the latest transaction ordering time for particular Node
     *  }
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function get_response_metadata(array $response): array;

    /**
     * Builds a AUTH_RULE request. Request to change authentication rules for a ledger transaction.
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     * Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     *
     * @param string $txn_type ledger transaction alias or associated value.
     * @param string $action type of an action.
     *      Can be either "ADD" (to add a new rule) or "EDIT" (to edit an existing one).
     * @param string $field transaction field.
     * @param string|null $old_value (Optional) old value of a field, which can be changed to a new_value (mandatory for EDIT action).
     * @param string|null $new_value (Optional) new value that can be used to fill the field.
     * @param array $constraint set of constraints required for execution of an action in the following format:
     *  {
     *      constraint_id - <string> type of a constraint.
     *          Can be either "ROLE" to specify final constraint or  "AND"/"OR" to combine constraints.
     *      role - <string> (optional) role of a user which satisfy to constrain.
     *      sig_count - <u32> the number of signatures required to execution action.
     *      need_to_be_owner - <bool> (optional) if user must be an owner of transaction (false by default).
     *      off_ledger_signature - <bool> (optional) allow signature of unknow for ledger did (false by default).
     *      metadata - <object> (optional) additional parameters of the constraint.
     *  }
     * can be combined by
     *  {
     *      'constraint_id': <"AND" or "OR">
     *      'auth_constraints': [<constraint_1>, <constraint_2>]
     *  }
     *
     * Default ledger auth rules: https://github.com/hyperledger/indy-node/blob/master/docs/source/auth_rules.md
     *
     * More about AUTH_RULE request: https://github.com/hyperledger/indy-node/blob/master/docs/source/requests.md#auth_rule
     *
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_auth_rule_request(
        string $submitter_did, string $txn_type, string $action, string $field,
        ?string $old_value, ?string $new_value, array $constraint
    ): array;

    /**
     * Builds a AUTH_RULES request. Request to change multiple authentication rules for a ledger transaction.
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param array $data a list of auth rules: [
     *      {
     *          "auth_type": ledger transaction alias or associated value,
     *          "auth_action": type of an action,
     *          "field": transaction field,
     *          "old_value": (Optional) old value of a field, which can be changed to a new_value (mandatory for EDIT action),
     *          "new_value": (Optional) new value that can be used to fill the field,
     *          "constraint": set of constraints required for execution of an action in the format described above for `build_auth_rule_request` function.
     *      }
     *  ]
     *
     * Default ledger auth rules: https://github.com/hyperledger/indy-node/blob/master/docs/source/auth_rules.md
     *
     * More about AUTH_RULE request: https://github.com/hyperledger/indy-node/blob/master/docs/source/requests.md#auth_rules
     *
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_auth_rules_request(string $submitter_did, array $data): array;

    /**
     * Builds a GET_AUTH_RULE request. Request to get authentication rules for a ledger transaction.
     *
     * NOTE: Either none or all transaction related parameters must be specified (`old_value` can be skipped for `ADD` action).
     *   * none - to get all authentication rules for all ledger transactions
     *   * all - to get authentication rules for specific action (`old_value` can be skipped for `ADD` action)
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param string|null $txn_type target ledger transaction alias or associated value.
     * @param string|null $action target action type. Can be either "ADD" or "EDIT".
     * @param string|null $field target transaction field.
     * @param string|null $old_value (Optional) old value of field, which can be changed to a new_value (must be specified for EDIT action).
     * @param string|null $new_value (Optional) new value that can be used to fill the field.
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_auth_rule_request(
        ?string $submitter_did, ?string $txn_type, ?string $action,
        ?string $field, ?string $old_value, ?string $new_value
    ): array;

    /**
     * Builds a TXN_AUTHR_AGRMT request. Request to add a new version of Transaction Author Agreement to the ledger.
     *
     * EXPERIMENTAL
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param string|null $text (Optional) a content of the TTA.
     *                               Mandatory in case of adding a new TAA. An existing TAA text can not be changed.
     *                               for Indy Node version <= 1.12.0:
     *                                  Use empty string to reset TAA on the ledger
     *                               for Indy Node version > 1.12.0
     *                                  Should be omitted in case of updating an existing TAA (setting `retirement_ts`)
     * @param string $version a version of the TTA (unique UTF-8 string).
     * @param int|null $ratification_ts (Optional) the date (timestamp) of TAA ratification by network government.
     *                              for Indy Node version <= 1.12.0:
     *                                  Must be omitted
     *                              for Indy Node version > 1.12.0:
     *                                  Must be specified in case of adding a new TAA
     *                                  Can be omitted in case of updating an existing TAA
     * @param int|null $retirement_ts (Optional) the date (timestamp) of TAA retirement.
     *                              for Indy Node version <= 1.12.0:
     *                                  Must be omitted
     *                              for Indy Node version > 1.12.0:
     *                                  Must be omitted in case of adding a new (latest) TAA.
     *                                  Should be used for updating (deactivating) non-latest TAA on the ledger.
     *
     * Note: Use `build_disable_all_txn_author_agreements_request` to disable all TAA's on the ledger.
     *
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_txn_author_agreement_request(
        string $submitter_did, ?string $text, string $version,
        int $ratification_ts = null, int $retirement_ts = null
    ): array;

    /**
     * Builds a DISABLE_ALL_TXN_AUTHR_AGRMTS request. Request to disable all Transaction Author Agreement on the ledger.
     *
     * EXPERIMENTAL
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     *                              Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     *
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_disable_all_txn_author_agreements_request(string $submitter_did): array;

    /**
     * Builds a GET_TXN_AUTHR_AGRMT request. Request to get a specific Transaction Author Agreement from the ledger.
     *
     * EXPERIMENTAL
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param array|null $data (Optional) specifies a condition for getting specific TAA.
     * Contains 3 mutually exclusive optional fields:
     *  {
     *      hash: Optional<str> - hash of requested TAA,
     *      version: Optional<str> - version of requested TAA.
     *      timestamp: Optional<i64> - ledger will return TAA valid at requested timestamp.
     *  }
     * Null data or empty JSON are acceptable here. In this case, ledger will return the latest version of TAA.
     *
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_txn_author_agreement_request(?string $submitter_did, array $data = null): array;

    /**
     * Builds a SET_TXN_AUTHR_AGRMT_AML request. Request to add a new list of acceptance mechanisms for transaction author agreement.
     * Acceptance Mechanism is a description of the ways how the user may accept a transaction author agreement.
     *
     * EXPERIMENTAL
     *
     * @param string $submitter_did Identifier (DID) of the transaction author as base58-encoded string.
     * Actual request sender may differ if Endorser is used (look at `append_request_endorser`)
     * @param array $aml a set of new acceptance mechanisms:
     *  {
     *      “<acceptance mechanism label 1>”: { acceptance mechanism description 1},
     *      “<acceptance mechanism label 2>”: { acceptance mechanism description 2},
     *      ...
     *  }
     * @param string $version a version of new acceptance mechanisms. (Note: unique on the Ledger)
     * @param string|null $aml_context (Optional) common context information about acceptance mechanisms (may be a URL to external resource).
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_acceptance_mechanisms_request(string $submitter_did, array $aml, string $version, ?string $aml_context): array;

    /**
     * Builds a GET_TXN_AUTHR_AGRMT_AML request. Request to get a list of  acceptance mechanisms from the ledger
     * valid for specified time or the latest one.
     *
     * EXPERIMENTAL
     *
     * @param string|null $submitter_did (Optional) DID of the read request sender (if not provided then default Libindy DID will be used).
     * @param int|null $timestamp (Optional) time to get an active acceptance mechanisms. The latest one will be returned for the empty timestamp.
     * @param string|null $version (Optional) version of acceptance mechanisms.
     *
     * NOTE: timestamp and version cannot be specified together.
     *
     * @return array Request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function build_get_acceptance_mechanisms_request(?string $submitter_did, ?int $timestamp, ?string $version): array;

    /**
     * Append transaction author agreement acceptance data to a request.
     * This function should be called before signing and sending a request
     * if there is any transaction author agreement set on the Ledger.
     *
     * EXPERIMENTAL
     *
     * This function may calculate hash by itself or consume it as a parameter.
     * If all text, version and taa_digest parameters are specified, a check integrity of them will be done.
     *
     * @param array $request original request data json.
     * @param string|null $text and
     * @param string|null $version (Optional) raw data about TAA from ledger.
     *                              These parameters should be passed together.
     *                              These parameters are required if taa_digest parameter is omitted.
     * @param string|null $taa_digest (Optional) digest on text and version.
     *                                  Digest is sha256 hash calculated on concatenated strings: version || text.
     *                                  This parameter is required if text and version parameters are omitted.
     * @param string $mechanism mechanism how user has accepted the TAA
     * @param int $time UTC timestamp when user has accepted the TAA. Note that the time portion will be discarded to avoid a privacy risk.
     * @return array Updated request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function append_txn_author_agreement_acceptance_to_request(
        array $request, ?string $text, ?string $version,
        ?string $taa_digest, string $mechanism, int $time
    ): array;

    /**
     * Append Endorser to an existing request.
     *
     * An author of request still is a `DID` used as a `submitter_did` parameter for the building of the request.
     * But it is expecting that the transaction will be sent by the specified Endorser.
     *
     * Note: Both Transaction Author and Endorser must sign output request after that.
     *
     * More about Transaction Endorser: https://github.com/hyperledger/indy-node/blob/master/design/transaction_endorser.md
     *                                  https://github.com/hyperledger/indy-sdk/blob/master/docs/configuration.md
     *
     * @param array $request original request data json.
     * @param string $endorser_did DID of the Endorser that will submit the transaction.
     * The Endorser's DID must be present on the ledger.
     * @return array Updated request result as json.
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    abstract public function append_request_endorser(array $request, string $endorser_did): array;

}