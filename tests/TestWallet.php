<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Agent\Wallet\Abstracts\Ledger\NYMRole;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;
use Siruis\Tests\Helpers\Conftest;

class TestWallet extends TestCase
{
    /** @test */
    public function test_crypto_pack_message()
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        $wallet_sender = $agent1->wallet;
        $wallet_recipient = $agent2->wallet;
        try {
            $verkey_sender = $wallet_sender->crypto->createKey();
            $verkey_recipient = $wallet_recipient->crypto->createKey();
            self::assertNotNull($verkey_sender);
            self::assertNotNull($verkey_recipient);
            $message = ['content' => 'Hello'];
            // #1: anon crypt mode
            $wired_message1 = $wallet_sender->crypto->pack_message($message, [$verkey_recipient]);
            $unpacked_message1 = $wallet_recipient->crypto->unpackMessage($wired_message1);
            self::assertEquals(json_encode($message), json_encode($unpacked_message1['message']));
            // #2: auth crypt mode
            $wired_message2 = $wallet_sender->crypto->pack_message($message, [$verkey_recipient], $verkey_sender);
            $unpacked_message2 = $wallet_recipient->crypto->unpackMessage($wired_message2);
            self::assertEquals(json_encode($message), json_encode($unpacked_message2['message']));
            self::assertNotEquals($wired_message1, $wired_message2);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /** @test */
    public function test_crypto_sign()
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        $wallet_signer = $agent1->wallet;
        $wallet_verifier = $agent2->wallet;
        try {
            $key_signer = $wallet_signer->crypto->createKey();
            $message = ['content' => 'Hello!'];
            $message_bytes = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $signature = $wallet_signer->crypto->cryptoSign($key_signer, $message_bytes);
            $is_ok = $wallet_verifier->crypto->cryptoVerify($key_signer, $message_bytes, $signature);
            self::assertTrue($is_ok);

            $key_signer2 = $wallet_signer->crypto->createKey();
            $broken_signature = $wallet_signer->crypto->cryptoSign($key_signer, $message_bytes);
            $is_ok = $wallet_verifier->crypto->cryptoVerify($key_signer2, $message_bytes, $broken_signature);
            self::assertFalse($is_ok);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /** @test */
    public function test_did_maintenance()
    {
        $agent1 = Conftest::agent1();
        $agent1->open();
        try {
            // #1: Create key
            $random_key = $agent1->wallet->did->create_key();
            self::assertNotNull($random_key);

            // #2: Set metadata
            $metadata = [
                'key1' => 'value1',
                'key2' => 'value2'
            ];
            $agent1->wallet->did->set_key_metadata($random_key, $metadata);
            sort($metadata);
            $expected = json_encode($metadata);
            $metadata = $agent1->wallet->did->get_key_metadata($random_key);
            sort($metadata);
            $actual = json_encode($metadata);
            self::assertEquals($expected, $actual);

            // #3: Create DID + Verkey
            list($did, $verkey) = $agent1->wallet->did->create_and_store_my_did();
            $fully = $agent1->wallet->did->qualify_did($did, 'peer');
            self::assertStringContainsString($did, $fully);

            // #4: Replace verkey
            $verkey_new = $agent1->wallet->did->replce_keys_start($fully);
            self::assertNotNull($verkey_new);
            $metadata_list = $agent1->wallet->did->list_my_dids_with_meta();
            self::assertStringContainsString($verkey_new, json_encode($metadata_list, JSON_UNESCAPED_SLASHES));
            $agent1->wallet->did->replace_keys_apply($fully);
            $metadata_list = $agent1->wallet->did->list_my_dids_with_meta();
            self::assertStringContainsString($verkey_new, json_encode($metadata_list, JSON_UNESCAPED_SLASHES));
            $actual_verkey = $agent1->wallet->did->key_for_local_did($fully);
            self::assertEquals($verkey_new, $actual_verkey);
        } finally {
            $agent1->close();
        }
    }
    
    /** @test */
    public function test_their_did_maintenance()
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        $wallet_me = $agent1->wallet;
        $wallet_their = $agent2->wallet;
        try {
            list($did_my, $verkey_my) = $wallet_me->did->create_and_store_my_did();
            list($did_their, $verkey_their) = $wallet_their->did->create_and_store_my_did();
            $wallet_me->did->store_their_did($did_their, $verkey_their);
            $metadata = [
                'key1' => 'value1',
                'key2' => 'value2'
            ];
            $wallet_me->did->set_did_metadata($did_their, $metadata);
            $expected = json_encode($metadata, JSON_UNESCAPED_SLASHES);
            $metadata = $wallet_me->did->get_did_metadata($did_their);
            $actual = json_encode($metadata, JSON_UNESCAPED_SLASHES);
            self::assertEquals($expected, $actual);

            $verkey = $wallet_me->did->key_for_local_did($did_their);
            self::assertEquals($verkey_their, $verkey);

            $verkey_their_new = $wallet_their->did->replce_keys_start($did_their);
            $wallet_their->did->replace_keys_apply($did_their);
            $wallet_me->did->store_their_did($did_their, $verkey_their_new);
            $verkey = $wallet_me->did->key_for_local_did($did_their);
            self::assertEquals($verkey_their_new, $verkey);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
    
    /** @test */
    public function test_record_value()
    {
        $agent = Conftest::agent1();
        $agent->open();
        try {
            $value = 'my-value-'.uniqid();
            $my_id = 'my-id-'.uniqid();
            $agent->wallet->non_secrets->add_wallet_record('type', $my_id, $value);
            $opts = new RetrieveRecordOptions();
            $opts->checkAll();
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($my_id, $value_info['id']);
            self::assertEquals($value, $value_info['value']);
            self::assertEquals('type', $value_info['type']);
            self::assertEmpty($value_info['tags']);

            $value_new = 'my-new-value-'.uniqid();
            $agent->wallet->non_secrets->update_wallet_record_value('type', $my_id, $value_new);
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($value_new, $value_info['value']);

            $agent->wallet->non_secrets->delete_wallet_record('type', $my_id);
        } finally {
            $agent->close();
        }
    }

    /** @test */
    public function test_record_value_with_tags()
    {
        $agent = Conftest::agent1();
        $agent->open();
        try {
            $value = 'my-value-'.uniqid();
            $my_id = 'my-id-'.uniqid();
            $tags = [
                'tag1' => 'val1',
                '~tag2' => 'val2'
            ];
            $agent->wallet->non_secrets->add_wallet_record('type', $my_id, $value, $tags);
            $opts = new RetrieveRecordOptions();
            $opts->checkAll();
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($my_id, $value_info['id']);
            self::assertEquals($value, $value_info['value']);
            self::assertEquals('type', $value_info['type']);
            self::assertEquals($tags, $value_info['tags']);

            $upd_tags = [
                'ext-tag' => 'val3'
            ];
            $agent->wallet->non_secrets->update_wallet_record_tags('type', $my_id, $upd_tags);
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($upd_tags, $value_info['tags']);

            $merged = array_merge($tags, $upd_tags);
            $agent->wallet->non_secrets->add_wallet_record_tags('type', $my_id, $tags);
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($merged, $value_info['tags']);

            $agent->wallet->non_secrets->delete_wallet_record_tags('type', $my_id, ['ext-tag']);
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($tags, $value_info['tags']);
        } finally {
            $agent->close();
        }
    }

    /** @test */
    public function test_record_value_with_tags_then_update()
    {
        $agent = Conftest::agent1();
        $agent->open();
        try {
            $value = 'my-value-'.uniqid();
            $my_id = 'my-id-'.uniqid();
            $agent->wallet->non_secrets->add_wallet_record('type', $my_id, $value);
            $opts = new RetrieveRecordOptions();
            $opts->checkAll();
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($my_id, $value_info['id']);
            self::assertEquals($value, $value_info['value']);
            self::assertEquals('type', $value_info['type']);
            self::assertEmpty($value_info['tags']);

            $tags1 = [
                'tag1' => 'val1',
                '~tag2' => 'val2'
            ];

            $agent->wallet->non_secrets->update_wallet_record_tags('type', $my_id, $tags1);
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($tags1, $value_info['tags']);

            $tags2 = [
                'tag3' => 'val3'
            ];
            $agent->wallet->non_secrets->update_wallet_record_tags('type', $my_id, $tags2);
            $value_info = $agent->wallet->non_secrets->get_wallet_record('type', $my_id, $opts);
            self::assertEquals($tags2, $value_info['tags']);
        } finally {
            $agent->close();
        }
    }

    /** @test */
    public function test_record_search()
    {
        $agent = Conftest::agent1();
        $agent->open();
        try {
            $id1 = 'id-1-'.uniqid();
            $id2 = 'id-2-'.uniqid();
            $value1 = 'value-1-'.uniqid();
            $value2 = 'value-2-'.uniqid();
            $marker_a = 'A-'.uniqid();
            $marker_b = 'B-'.uniqid();
            $opts = new RetrieveRecordOptions();
            $opts->checkAll();
            $tags1 = [
                'tag1' => $value1,
                '~tag2' => '5',
                'marker' => $marker_a
            ];
            $tags2 = [
                'tag3' => 'val3',
                '~tag4' => $value2,
                'marker' => $marker_b
            ];
            $agent->wallet->non_secrets->add_wallet_record('type', $id1, 'value1', $tags1);
            $agent->wallet->non_secrets->add_wallet_record('type', $id2, 'value2', $tags2);

            $query = [
                'tag1' => $value1
            ];
            list($records, $total) = $agent->wallet->non_secrets->wallet_search('type', $query, $opts);
            self::assertEquals(1, $total);
            self::assertStringContainsString('value-1', json_encode($records, JSON_UNESCAPED_SLASHES));

            $query = [
                '$or' => [
                    ['tag1' => $value1],
                    ['~tag4' => $value2]
                ]
            ];

            list($records, $total) = $agent->wallet->non_secrets->wallet_search('type', $query, $opts);
            self::assertCount(1, $records);
            self::assertEquals(2, $total);

            $query = [
                'marker' => ['$in' => [$marker_a, $marker_b]]
            ];
            list($records, $total) = $agent->wallet->non_secrets->wallet_search('type', $query, $opts);
            self::assertEquals(2, $total);
        } finally {
            $agent->close();
        }
    }

    /** @test */
    public function test_register_schema_in_network()
    {
        $agent = Conftest::agent2();
        $default_network = Conftest::default_network();
        $agent->open();
        try {
            $seed = '000000000000000000000000Trustee1';
            list($did, $verkey) = $agent->wallet->did->create_and_store_my_did(null, $seed);
            $schema_name = 'schema_'.uniqid();
            list($schema_id, $schema) = $agent->wallet->anoncreds->issuer_create_schema($did, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']);

            list($ok, $response) = $agent->wallet->ledger->register_schema($default_network, $did, $schema->body);
            self::assertTrue($ok);
        } finally {
            $agent->close();
        }
    }

    /** @test */
    public function test_register_cred_def_in_network()
    {
        $agent = Conftest::agent2();
        $default_network = Conftest::default_network();
        $agent->open();
        try {
            $seed = '000000000000000000000000Trustee1';
            list($did, $verkey) = $agent->wallet->did->create_and_store_my_did(null, $seed);
            $schema_name = 'schema_'.uniqid();
            list($schema_id, $schema) = $agent->wallet->anoncreds->issuer_create_schema($did, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']);


            list ($ok, $response) = $agent->wallet->ledger->register_schema($default_network, $did, $schema->body);
            self::assertTrue($ok);

            $opts = new CacheOptions();
            $schema_from_ledger = $agent->wallet->cache->get_schema($default_network, $did, $schema_id, $opts);

            list($cred_def_id, $cred_def) = $agent->wallet->anoncreds->issuer_create_and_store_credential_def(
                $did, $schema_from_ledger, 'TAG'
            );
            list($ok, $response) = $agent->wallet->ledger->register_cred_def($default_network, $did, $cred_def);
            self::assertTrue($ok);
        } finally {
            $agent->close();
        }
    }

    /** @test */
    public function test_nym_operations_in_network()
    {
        $default_network = Conftest::default_network();
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        $steward = $agent1->wallet;
        $actor = $agent2->wallet;
        try {
            $seed = '000000000000000000000000Steward1';
            list($did_steward, $verkey_steward) = $steward->did->create_and_store_my_did(null, $seed);
            list($did_trustee, $verkey_trustee) = $actor->did->create_and_store_my_did();
            list($did_common, $verkey_common) = $actor->did->create_and_store_my_did();

            // Trust Anchor
            list($ok, $response) = $steward->ledger->write_nym(
                $default_network, $did_steward, $did_trustee, $verkey_trustee, 'Test-Trustee', NYMRole::TRUST_ANCHOR()
            );
            self::assertTrue($ok);
            list($ok, $nym1) = $steward->ledger->read_nym($default_network, $did_steward, $did_trustee);
            self::assertTrue($ok);
            list($ok, $nym2) = $steward->ledger->read_nym($default_network, null, $did_trustee);
            self::assertTrue($ok);
            self::assertEquals(json_encode($nym1, JSON_UNESCAPED_SLASHES), json_encode($nym2, JSON_UNESCAPED_SLASHES));
            self::assertEquals((string)array_values(NYMRole::TRUST_ANCHOR)[0], $nym1['role']);

            // Common user
            list($ok, $response) = $steward->ledger->write_nym(
                $default_network, $did_steward, $did_common, $verkey_common, 'CommonUser', NYMRole::COMMON_USER()
            );
            self::assertTrue($ok);
            list($ok, $nym3) = $steward->ledger->read_nym($default_network, null, $did_common);
            self::assertTrue($ok);
            self::assertNull($nym3['role']);

            // Reset
            list($ok, $response) = $actor->ledger->write_nym(
                $default_network, $did_common, $did_common, $verkey_common, 'ResetUser', NYMRole::RESET()
            );
            self::assertTrue($ok);
            list($ok, $nym4) = $steward->ledger->read_nym(
                $default_network, null, $did_common
            );
            self::assertTrue($ok);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
}
