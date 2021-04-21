<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Tests\Helpers\Conftest;

class TestWallet extends TestCase
{
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
            self::assertEquals(json_encode($message), json_encode($unpacked_message1));
            // #2: auth crypt mode
            $wired_message2 = $wallet_sender->crypto->pack_message($message, [$verkey_recipient], $verkey_sender);
            $unpacked_message2 = $wallet_recipient->crypto->unpackMessage($wired_message2);
            self::assertEquals(json_encode($message), json_encode($unpacked_message2));
            self::assertNotEquals($wired_message1, $wired_message2);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

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
            $message = ['content' => 'Hello'];
            $message_bytes = json_encode($message);
            $signature = $wallet_signer->crypto->cryptoSign($key_signer, $message_bytes);
            $is_ok = $wallet_verifier->crypto->cryptoVerify($key_signer, $message_bytes, $signature);
            self::assertTrue($is_ok);

            $key_signer2 = $wallet_signer->crypto->createKey();
            $broken_signature = $wallet_signer->crypto->cryptoSign($key_signer2, $message_bytes);
            $is_ok = $wallet_verifier->crypto->cryptoVerify($key_signer2, $message_bytes, $broken_signature);
            self::assertFalse($is_ok);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

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
            foreach ($metadata_list as $m) {
                self::assertEquals($verkey_new, $m['tempVerkey']);
            }
            $agent1->wallet->did->replace_keys_apply($fully);
            $metadata_list = $agent1->wallet->did->list_my_dids_with_meta();
            foreach ($metadata_list as $m) {
                self::assertEquals($verkey_new, $m['verkey']);
            }
            $actual_verkey = $agent1->wallet->did->key_for_local_did($fully);
            self::assertEquals($verkey_new, $actual_verkey);
        } finally {
            $agent1->close();
        }
    }
}
