<?php

namespace Siruis\Tests;

use Exception;
use Siruis\Encryption\Ed25519;
use PHPUnit\Framework\TestCase;
use Siruis\Encryption\Encryption;

class EncryptionTest extends TestCase
{
    /** @test */
    public function test_create_keypair_from_seed()
    {
        $keys = Encryption::create_keypair(b'0000000000000000000000000000SEED');
        $verkey = Encryption::bytes_to_b58($keys['verkey']);
        $sigkey = Encryption::bytes_to_b58($keys['sigkey']);
        self::assertEquals($verkey, 'GXhjv2jGf2oT1sqMyvJtgJxNYPMHmTsdZ3c2ZYQLJExj');
        self::assertEquals($sigkey, 'xt19s1sp2UZCGhy9rNyb1FtxdKiDGZZPNFnc1KyoHNK9SDgzvPrapQPJVL9sh3e87ESLpJdwvFdxwHXagYjcaA7');
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_sane()
    {
        $recipient_keys = Encryption::create_keypair(b'000000000000000000000000000SEED1');
        $verkey_recipient = Encryption::bytes_to_b58($recipient_keys['verkey']);
        $sigkey_recipient = Encryption::bytes_to_b58($recipient_keys['sigkey']);
        $sender_keys = Encryption::create_keypair(b'000000000000000000000000000SEED2');
        $verkey_sender = Encryption::bytes_to_b58($sender_keys['verkey']);
        $sigkey_sender = Encryption::bytes_to_b58($sender_keys['sigkey']);
        $message = [
            'content' => 'TestMessages encryption строка'
        ];
        $message = json_encode($message);
        $to_verkeys = [];
        array_push($to_verkeys, $verkey_recipient);
        $packed = Ed25519::pack_message($message, $to_verkeys, $verkey_sender, $sigkey_sender);
        $unpacked = Ed25519::unpack_message($packed, $verkey_recipient, $sigkey_recipient);
        $unpacked_message = $unpacked[0];
        $sender_vk = $unpacked[1];
        $recip_vk = $unpacked[2];
        self::assertEquals($message, $unpacked_message);
        self::assertEquals($sender_vk, $verkey_sender);
        self::assertEquals($recip_vk, $verkey_recipient);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_fixture()
    {
        $recipient_keys = Encryption::create_keypair(b'000000000000000000000000000SEED1');
        $verkey_recipient = Encryption::bytes_to_b58($recipient_keys['verkey']);
        $sigkey_recipient = Encryption::bytes_to_b58($recipient_keys['sigkey']);
        $sender_keys = Encryption::create_keypair(b'000000000000000000000000000SEED2');
        $verkey_sender = Encryption::bytes_to_b58($sender_keys['verkey']);
        $packed = b'{"protected": "eyJlbmMiOiAieGNoYWNoYTIwcG9seTEzMDVfaWV0ZiIsICJ0eXAiOiAiSldNLzEuMCIsICJhbGciOiAiQXV0aGNyeXB0IiwgInJlY2lwaWVudHMiOiBbeyJlbmNyeXB0ZWRfa2V5IjogInBKcW1xQS1IVWR6WTNWcFFTb2dySGx4WTgyRnc3Tl84YTFCSmtHU2VMT014VUlwT0RQWTZsMVVsaVVvOXFwS0giLCAiaGVhZGVyIjogeyJraWQiOiAiM1ZxZ2ZUcDZRNFZlRjhLWTdlVHVXRFZBWmFmRDJrVmNpb0R2NzZLR0xtZ0QiLCAic2VuZGVyIjogIjRlYzhBeFRHcWtxamd5NHlVdDF2a0poeWlYZlNUUHo1bTRKQjk1cGZSMG1JVW9KajAwWmswNmUyUEVDdUxJYmRDck8xeTM5LUhGTG5NdW5YQVJZWk5rZ2pyYV8wYTBQODJpbVdNcWNHc1FqaFd0QUhOcUw1OGNkUUYwYz0iLCAiaXYiOiAiVU1PM2o1ZHZwQnFMb2Rvd3V0c244WEMzTkVqSWJLb2oifX1dfQ==", "iv": "MchkHF2M-4hneeUJ", "ciphertext": "UgcdsV-0rIkP25eJuRSROOuqiTEXp4NToKjPMmqqtJs-Ih1b5t3EEbrrHxeSfPsHtlO6J4OqA1jc5uuD3aNssUyLug==", "tag": "sQD8qgJoTrRoyQKPeCSBlQ=="}';
        $unpacked = Ed25519::unpack_message($packed, $verkey_recipient, $sigkey_recipient);
        $unpacked_message = $unpacked[0];
        $sender_vk = $unpacked[1];
        $recip_vk = $unpacked[2];
        $message = '{"content": "Test encryption \u0441\u0442\u0440\u043e\u043a\u0430"}';
        self::assertEquals($message, $unpacked_message);
        self::assertEquals($sender_vk, $verkey_sender);
        self::assertEquals($recip_vk, $verkey_recipient);
    }

    /**
     * @test
     */
    public function test_crypto_sign()
    {
        $keys = Encryption::create_keypair(b'0000000000000000000000000000SEED');
        $verkey = $keys['verkey'];
        $sigkey = $keys['sigkey'];
        $msg = b'message';
        $signature = Encryption::sign_message($msg, $sigkey);
        self::assertEquals('3tfqJYZ8ME8gTFUSHcH4uVTUx5kV7S1qPJJ65k2VtSocMfXvnzR1sbbfq6F2RcXrFtaufjEr4KQVu7aeyirYrcRm', Encryption::bytes_to_b58($signature));

        $success = Encryption::verify_signed_message($verkey, $msg, $signature);
        self::assertTrue($success);

        $keys2 = Encryption::create_keypair(b'000000000000000000000000000SEED2');
        $verkey2 = $keys2['verkey'];
        $sigkey2 = $keys2['sigkey'];
        self::assertNotEquals($verkey, $verkey2);
        $signature = Encryption::sign_message($msg, $sigkey2);
        $success = Encryption::verify_signed_message($verkey, $msg, $signature);
        self::assertFalse($success);
    }
    
    /** @test */
    public function test_did_from_verkey()
    {
        $keys = Encryption::create_keypair(b'0000000000000000000000000000SEED');
        $verkey = $keys['verkey'];
        self::assertEquals('GXhjv2jGf2oT1sqMyvJtgJxNYPMHmTsdZ3c2ZYQLJExj', Encryption::bytes_to_b58($verkey));
        $did = Encryption::did_from_verkey($verkey);
        self::assertEquals('VVZbGvuFqBdoVNY1Jh4j9Q', Encryption::bytes_to_b58($did));
    }
}
