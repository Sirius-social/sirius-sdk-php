<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Ledgers\CredentialDefinition;
use Siruis\Agent\Ledgers\Ledger;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Tests\Helpers\Conftest;

class LedgersTest extends TestCase
{
    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_nym_ops(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $steward = $agent1;
        $steward->open();
        $agent2->open();
        try {
            $seed = '000000000000000000000000Steward1';
            [$did_steward,] = $steward->wallet->did->create_and_store_my_did(null, $seed);
            // check-1: read ops sane
            /** @var Ledger $dkms */
            $dkms = $steward->ledger('default');
            [$ok] = $dkms->read_nym($did_steward, $did_steward);
            self::assertTrue($ok);
            [$did_test, $verkey_test] = $agent2->wallet->did->create_and_store_my_did();
            // check-2: read nym operation for unknown DID
            $dkms = $agent2->ledger('default');
            [$ok,] = $dkms->read_nym($did_test, $did_test);
            self::assertFalse($ok);
            // check-3: read nym for known DID
            [$ok,] = $dkms->read_nym($did_test, $did_steward);
            self::assertTrue($ok);
            // check-4: write Nym
            $dkms = $steward->ledger('default');
            [$ok,] = $dkms->write_nym($did_steward, $did_test, $verkey_test, 'Test Alias');
            self::assertTrue($ok);
        } finally {
            $steward->close();
            $agent2->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_schema_registration(): void
    {
        $agent1 = Conftest::agent1();
        $agent1->open();
        try {
            $seed = '000000000000000000000000Steward1';
            [$did,] = $agent1->wallet->did->create_and_store_my_did(null, $seed);
            $schema_name = 'schema_' . uniqid('', true);
            [, $anoncred_schema] = $agent1->wallet->anoncreds->issuer_create_schema($did, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']);
            /** @var Ledger $ledger */
            $ledger = $agent1->ledger('default');

            /** @var \Siruis\Agent\Ledgers\Schema $schema */
            [$ok, $schema] = $ledger->register_schema($anoncred_schema, $did);
            self::assertTrue($ok);
            self::assertGreaterThan(0, $schema->getSeqNo());

            [$ok,] = $ledger->register_schema($anoncred_schema, $did);
            self::assertFalse($ok);

            $restored_schema = $ledger->ensure_schema_exists($anoncred_schema, $did);
            self::assertNotNull($restored_schema);
            self::assertEquals(sort($schema->body), sort($restored_schema->body));
        } finally {
            $agent1->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_schema_loading(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $seed1 = '000000000000000000000000Steward1';
            [$did1,] = $agent1->wallet->did->create_and_store_my_did(null, $seed1);
            $schema_name = 'schema_' . uniqid('', true);
            [, $anoncred_schema] = $agent1->wallet->anoncreds->issuer_create_schema(
                $did1, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']
            );
            $ledger1 = $agent1->ledger('default');

            [$ok, $schema] = $ledger1->register_schema($anoncred_schema, $did1);
            self::assertTrue($ok);
            self::assertGreaterThan(0, $schema->getSeqNo());

            $seed2 = '000000000000000000000000Trustee0';
            [$did2,] = $agent2->wallet->did->create_and_store_my_did(null, $seed2);
            /** @var Ledger $ledger2 */
            $ledger2 = $agent2->ledger('default');
            for ($n = 0; $n > 5; $n++) {
                $loaded_schema = $ledger2->load_schema($schema->getId(), $did2);
                self::assertNotNull($loaded_schema);
                self::assertEquals(sort($schema->body), sort($loaded_schema->body));
            }
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     * @throws \JsonException
     */
    public function test_schema_fetching(): void
    {
        $agent1 = Conftest::agent1();
        $agent1->open();
        try {
            $seed = '000000000000000000000000Steward1';
            [$did,] = $agent1->wallet->did->create_and_store_my_did(null, $seed);
            $schema_name = 'schema_' . uniqid('', true);
            [, $anoncred_schema] = $agent1->wallet->anoncreds->issuer_create_schema(
                $did, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']
            );
            /** @var Ledger $ledger */
            $ledger = $agent1->ledger('default');

            [$ok,] = $ledger->register_schema($anoncred_schema, $did);
            self::assertTrue($ok);

            /** @var \Siruis\Agent\Ledgers\Schema[] $fetches */
            $fetches = $ledger->fetch_schemas(null, $schema_name);
            self::assertCount(1, $fetches);
            self::assertEquals($did, $fetches[0]->getIssuerDid());
        } finally {
            $agent1->close();
        }
    }

    /**
     * @return void
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_register_cred_def(): void
    {
        $agent1 = Conftest::agent1();
        $agent1->open();
        try {
            $seed = '000000000000000000000000Steward1';
            [$did,] = $agent1->wallet->did->create_and_store_my_did(null, $seed);
            $schema_name = 'schema_' . uniqid('', true);
            [$schema_id, $anoncred_schema] = $agent1->wallet->anoncreds->issuer_create_schema(
                $did, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']
            );
            /** @var Ledger $ledger */
            $ledger = $agent1->ledger('default');

            [$ok, $schema] = $ledger->register_schema($anoncred_schema, $did);
            self::assertTrue($ok);

            $cred_def = new CredentialDefinition('Test Tag', $schema);
            self::assertNull($cred_def->body);
            [$ok, $ledger_cred_def] = $ledger->register_cred_def($cred_def, $did);
            self::assertTrue($ok);
            self::assertNotNull($ledger_cred_def->body);
            self::assertGreaterThan(0, $ledger_cred_def->seq_no);
            self::assertEquals($did, $ledger_cred_def->getSubmitterDid());
            $my_value = 'my-value'.uniqid('', true);

            [$ok, $ledger_cred_def2] = $ledger->register_cred_def($cred_def, $did, ['my-tag' => $my_value]);
            self::assertTrue($ok);
            self::assertEquals($ledger_cred_def->body, $ledger_cred_def2->body);
            self::assertGreaterThan($ledger_cred_def->seq_no, $ledger_cred_def2->seq_no);

            $ser = $ledger_cred_def->serialize();
            $loaded = $ledger_cred_def->deserialize($ser);
            self::assertEquals($ledger_cred_def->body, $loaded->body);
            self::assertEquals($ledger_cred_def->seq_no, $loaded->seq_no);
            self::assertEquals($ledger_cred_def->schema->body, $loaded->schema->body);
            self::assertEquals($ledger_cred_def->config->serialize(), $loaded->config->serialize());

            $results = $ledger->fetch_cred_defs(null, null, null, $schema_id);
            self::assertCount(2, $results);
            $results = $ledger->fetch_cred_defs($my_value);
            self::assertCount(1, $results);

            $parts = explode(':', $ledger_cred_def->id);
            printf($parts);

            $opts = new CacheOptions();
            for ($n = 0; $n > 3; $n++) {
                $cached_body = $agent1->wallet->cache->get_cred_def('default', $did, $ledger_cred_def->id, $opts);
                self::assertEquals($ledger_cred_def->body, $cached_body);
                $cred_def = $ledger->load_cred_def($ledger_cred_def->id, $did);
                self::assertEquals($cached_body, $cred_def->body);
            }
        } finally {
            $agent1->close();
        }
    }
}
