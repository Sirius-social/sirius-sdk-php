<?php


namespace Siruis\Agent\Ledgers;


use Siruis\Agent\Wallet\Abstracts\AbstractCache;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AbstractAnonCreds;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AnonCredSchema;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Agent\Wallet\Abstracts\Ledger\AbstractLedger;
use Siruis\Agent\Wallet\Abstracts\Ledger\NYMRole;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\IndyExceptions\LedgerNotFound;
use Siruis\Helpers\ArrayHelper;
use Siruis\Storage\Abstracts\AbstractImmutableCollection;

class Ledger
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var \Siruis\Agent\Wallet\Abstracts\Ledger\AbstractLedger
     */
    public $api;
    /**
     * @var \Siruis\Agent\Wallet\Abstracts\Anoncreds\AbstractAnonCreds
     */
    public $issuer;
    /**
     * @var \Siruis\Agent\Wallet\Abstracts\AbstractCache
     */
    public $cache;
    /**
     * @var \Siruis\Storage\Abstracts\AbstractImmutableCollection
     */
    public $storage;
    /**
     * @var string
     */
    public $db;

    /**
     * Ledger constructor.
     * @param string $name
     * @param \Siruis\Agent\Wallet\Abstracts\Ledger\AbstractLedger $api
     * @param \Siruis\Agent\Wallet\Abstracts\Anoncreds\AbstractAnonCreds $issuer
     * @param \Siruis\Agent\Wallet\Abstracts\AbstractCache $cache
     * @param \Siruis\Storage\Abstracts\AbstractImmutableCollection $storage
     */
    public function __construct(
        string $name, AbstractLedger $api, AbstractAnonCreds $issuer,
        AbstractCache $cache, AbstractImmutableCollection $storage
    )
    {
        $this->name = $name;
        $this->api = $api;
        $this->issuer = $issuer;
        $this->cache = $cache;
        $this->storage = $storage;
        $this->db = 'ledger_storage_' . $name;
    }

    /**
     * @param string $submitter_did
     * @param string $target_did
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function read_nym(string $submitter_did, string $target_did): array
    {
        [$success, $data] = $this->api->read_nym($this->name, $submitter_did, $target_did);
        return [$success, $data];
    }

    /**
     * @param string $submitter_did
     * @param string $target_did
     * @param string|null $ver_key
     * @param string|null $alias
     * @param null $role
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function write_nym(
        string $submitter_did, string $target_did,
        string $ver_key = null, string $alias = null, $role = null): array
    {
        [$success, $data] = $this->api->write_nym(
            $this->name,
            $submitter_did,
            $target_did,
            $ver_key,
            $alias,
            $role ?: NYMRole::COMMON_USER()
        );
        return [$success, $data];
    }

    /**
     * @param string $id
     * @param string $submitter_did
     * @return \Siruis\Agent\Ledgers\Schema
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function load_schema(string $id, string $submitter_did): Schema
    {
        $body = $this->cache->get_schema(
            $this->name, $submitter_did, $id, new CacheOptions()
        );
        return new Schema($body);
    }

    /**
     * @param string $id
     * @param string $submitter_did
     * @return \Siruis\Agent\Ledgers\CredentialDefinition
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function load_cred_def(string $id, string $submitter_did): CredentialDefinition
    {
        $cred_def_body = $this->cache->get_cred_def(
            $this->name, $submitter_did, $id, new CacheOptions()
        );
        $tag = $cred_def_body['tag'];
        $schema_seq_no = (int)$cred_def_body['schemaId'];
        $exploded = (int)explode(':', $cred_def_body['id'])[3];
        $cred_def_seq_no = $exploded + 1;
        $txn_request = $this->api->build_get_txn_request(
            $submitter_did, null, $schema_seq_no
        );
        $resp = $this->api->sign_and_submit_request(
            $this->name,
            $submitter_did,
            $txn_request
        );
        if ($resp['op'] === 'REPLY') {
            $txn_data = $resp['result']['data'];
            $schema_body = [
                'name' => $txn_data['txn']['data']['data']['name'],
                'version' => $txn_data['txn']['data']['data']['version'],
                'attrNames' => $txn_data['txn']['data']['data']['attr_names'],
                'id' => $txn_data['txnMetadata']['txnId'],
                'seqNo' => $txn_data['txnMetadata']['seqNo']
            ];
            $schema_body['ver'] = explode(':', $schema_body['id'])[-1];
            $schema = new Schema($schema_body);
            return new CredentialDefinition(
                $tag, $schema, null, $cred_def_body, $cred_def_seq_no
            );
        }

        throw new SiriusInvalidPayloadStructure();
    }

    /**
     * @param \Siruis\Agent\Wallet\Abstracts\Anoncreds\AnonCredSchema $schema
     * @param string $submitter_did
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function register_schema(AnonCredSchema $schema, string $submitter_did): array
    {
        [$success, $txn_response] = $this->api->register_schema(
            $this->name,
            $submitter_did,
            $schema->body
        );
        if ($success && $txn_response['op'] === 'REPLY') {
            $body = $schema->body;
            $body['seqNo'] = $txn_response['result']['txnMetadata']['seqNo'];
            $schema_in_ledger = new Schema($body);
            $this->ensure_exists_in_storage($schema_in_ledger, $submitter_did);
            return [true, $schema_in_ledger];
        }

        $reason = ArrayHelper::getValueWithKeyFromArray('reason', $txn_response);
        if ($reason) {
            printf($reason.'\n');
        }
        return [false, null];
    }

    /**
     * @param \Siruis\Agent\Ledgers\CredentialDefinition $cred_def
     * @param string $submitter_did
     * @param array|null $tags
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function register_cred_def(
        CredentialDefinition $cred_def, string $submitter_did, array $tags = null
    ): array
    {
        [, $body] = $this->issuer->issuer_create_and_store_credential_def(
            $submitter_did,
            $cred_def->schema->body,
            $cred_def->tag,
            null,
            $cred_def->config->serialize()
        );
        $build_request = $this->api->build_cred_def_request(
            $submitter_did, $body
        );
        $signed_request = $this->api->sign_request($submitter_did, $build_request);
        $resp = $this->api->submit_request($this->name, $signed_request);
        $success = ArrayHelper::getValueWithKeyFromArray('op', $resp) === 'REPLY';
        if ($success) {
            $txn_response = $resp;
            $ledger_cred_def = new CredentialDefinition(
                $cred_def->tag, $cred_def->schema, $cred_def->config, $body,
                $txn_response['result']['txnMetadata']['seqNo']
            );
            $this->ensure_exists_in_storage($ledger_cred_def, $submitter_did, $tags);
            return [true, $ledger_cred_def];
        }

        return [false, null];
    }

    /**
     * @param \Siruis\Agent\Wallet\Abstracts\Anoncreds\AnonCredSchema $schema
     * @param string $submitter_did
     * @return \Siruis\Agent\Ledgers\Schema|null
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function ensure_schema_exists(AnonCredSchema $schema, string $submitter_did): ?Schema
    {
        try {
            $body = $this->cache->get_schema(
                $this->name, $submitter_did, $schema->getId(), new CacheOptions()
            );
            $ledger_schema = new Schema($body);
            $this->ensure_exists_in_storage($ledger_schema, $submitter_did);
            return $ledger_schema;
        } catch (LedgerNotFound $e) {
            echo $e;
        }
        [$ok, $ledger_schema] = $this->register_schema($schema, $submitter_did);
        if ($ok) {
            return $ledger_schema;
        }

        return null;
    }

    /**
     * @param string|null $id
     * @param string|null $name
     * @param string|null $version
     * @param string|null $submitter_did
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     * @throws \JsonException
     */
    public function fetch_schemas(
        string $id = null, string $name = null, string $version = null, string $submitter_did = null
    ): array
    {
        $filters = new SchemaFilters();
        if ($id) {
            $filters->setId($id);
        }
        if ($name) {
            $filters->setName($name);
        }
        if ($version) {
            $filters->setVersion($version);
        }
        if ($submitter_did) {
            $filters->setSubmitterDid($submitter_did);
        }
        $this->storage->select_db($this->db);
        [$fetched,] = $this->storage->fetch($filters->tags);
        $schemas = [];
        foreach ($fetched as $item) {
            $schemas[] = (new Schema)->deserialize($item);
        }
        return $schemas;
    }

    /**
     * @param string|null $tag
     * @param string|null $id
     * @param string|null $submitter_did
     * @param string|null $schema_id
     * @param int|null $seq_no
     * @param array|null $extras
     * @return array
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function fetch_cred_defs(
        string $tag = null, string $id = null, string $submitter_did = null,
        string $schema_id = null, int $seq_no = null, array $extras = null
    ): array
    {
        $filters = new CredentialDefinitionFilters();
        if ($tag) {
            $filters->setTag($tag);
        }
        if ($id) {
            $filters->setId($id);
        }
        if ($submitter_did) {
            $filters->setSubmitterDid($submitter_did);
        }
        if ($schema_id) {
            $filters->setSchemaId($schema_id);
        }
        if ($seq_no) {
            $filters->setSeqNo($seq_no);
        }
        if ($extras) {
            $filters->setExtras($extras);
        }
        $this->storage->select_db($this->db);
        $storageFetch = $this->storage->fetch($filters->tags);
        $cred_defs = [];
        foreach ($storageFetch[0] as $item) {
            $cred_defs[] = CredentialDefinition::unserialize($item);
        }
        return $cred_defs;
    }

    /**
     * @param Schema|CredentialDefinition $entity
     * @param string $submitter_did
     * @param array|null $search_tags
     * @return void
     */
    protected function ensure_exists_in_storage($entity, string $submitter_did, array $search_tags = null): void
    {
        $this->storage->select_db($this->db);
        if ($entity instanceof Schema) {
            $schema = $entity;
            $tags = [
                'id' => $schema->getId(),
                'category' => 'schema'
            ];
            [, $count] = $this->storage->fetch($tags);
            if ($count === 0) {
                $tags = array_merge($tags, [
                    'id' => $schema->getId(),
                    'name' => $schema->getName(),
                    'version' => $schema->getVersion(),
                    'submitter_did' => $submitter_did
                ]);
                $this->storage->add($schema->serialize(), $tags);
            }
        } elseif ($entity instanceof CredentialDefinition) {
            $cred_def = $entity;
            $tags = [
                'id' => $cred_def->getId(),
                'seq_no' => (string)$cred_def->seq_no,
                'category' => 'cred_def'
            ];
            [,$count] = $this->storage->fetch($tags);
            if ($count === 0) {
                $tags = array_merge($tags, [
                    'id' => $cred_def->getId(),
                    'tag' => $cred_def->tag,
                    'schema_id' => $cred_def->schema->getId(),
                    'submitter_did' => $cred_def->getSubmitterDid()
                ]);
            }
            if ($search_tags) {
                $tags = array_merge($tags, $search_tags);
            }
            $this->storage->add($cred_def->serialize(), $tags);
        }
    }
}