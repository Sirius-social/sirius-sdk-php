<?php

namespace Siruis\Tests;

use Exception;
use Siruis\Encryption\Ed25519;
use PHPUnit\Framework\TestCase;

class EncryptionTest extends TestCase
{
    /**
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     */
    public function test_create_keypair_from_seed(): void
    {
        [$verkey, $sigkey] = create_keypair(b'0000000000000000000000000000SEED');
        $verkey = bytes_to_b58($verkey);
        $sigkey = bytes_to_b58($sigkey);
        self::assertEquals('GXhjv2jGf2oT1sqMyvJtgJxNYPMHmTsdZ3c2ZYQLJExj', $verkey);
        self::assertEquals('xt19s1sp2UZCGhy9rNyb1FtxdKiDGZZPNFnc1KyoHNK9SDgzvPrapQPJVL9sh3e87ESLpJdwvFdxwHXagYjcaA7', $sigkey);
    }

    /**
     * @throws Exception
     */
    public function test_sane(): void
    {
        [$verkey, $sigkey] = create_keypair(b'000000000000000000000000000SEED1');
        $verkey_recipient = bytes_to_b58($verkey);
        $sigkey_recipient = bytes_to_b58($sigkey);
        [$verkey, $sigkey] = create_keypair(b'000000000000000000000000000SEED2');
        $verkey_sender = bytes_to_b58($verkey);
        $sigkey_sender = bytes_to_b58($sigkey);
        $message = [
            'content' => 'TestMessages encryption строка'
        ];
        $message = json_encode($message, JSON_THROW_ON_ERROR);
        $to_verkeys = [];
        $to_verkeys[] = $verkey_recipient;
        $packed = Ed25519::pack_message($message, $to_verkeys, $verkey_sender, $sigkey_sender);
        [$unpacked_message, $sender_vk, $recip_vk] = Ed25519::unpack_message($packed, $verkey_recipient, $sigkey_recipient);
        self::assertEquals($message, $unpacked_message);
        self::assertEquals($sender_vk, $verkey_sender);
        self::assertEquals($recip_vk, $verkey_recipient);
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     * @throws \Exception
     */
    public function test_fixture(): void
    {
        [$verkey, $sigkey] = create_keypair(b'000000000000000000000000000SEED1');
        $verkey_recipient = bytes_to_b58($verkey);
        $sigkey_recipient = bytes_to_b58($sigkey);
        [$verkey,] = create_keypair(b'000000000000000000000000000SEED2');
        $verkey_sender = bytes_to_b58($verkey);
        $packed = b'{"protected": "eyJlbmMiOiAieGNoYWNoYTIwcG9seTEzMDVfaWV0ZiIsICJ0eXAiOiAiSldNLzEuMCIsICJhbGciOiAiQXV0aGNyeXB0IiwgInJlY2lwaWVudHMiOiBbeyJlbmNyeXB0ZWRfa2V5IjogInBKcW1xQS1IVWR6WTNWcFFTb2dySGx4WTgyRnc3Tl84YTFCSmtHU2VMT014VUlwT0RQWTZsMVVsaVVvOXFwS0giLCAiaGVhZGVyIjogeyJraWQiOiAiM1ZxZ2ZUcDZRNFZlRjhLWTdlVHVXRFZBWmFmRDJrVmNpb0R2NzZLR0xtZ0QiLCAic2VuZGVyIjogIjRlYzhBeFRHcWtxamd5NHlVdDF2a0poeWlYZlNUUHo1bTRKQjk1cGZSMG1JVW9KajAwWmswNmUyUEVDdUxJYmRDck8xeTM5LUhGTG5NdW5YQVJZWk5rZ2pyYV8wYTBQODJpbVdNcWNHc1FqaFd0QUhOcUw1OGNkUUYwYz0iLCAiaXYiOiAiVU1PM2o1ZHZwQnFMb2Rvd3V0c244WEMzTkVqSWJLb2oifX1dfQ==", "iv": "MchkHF2M-4hneeUJ", "ciphertext": "UgcdsV-0rIkP25eJuRSROOuqiTEXp4NToKjPMmqqtJs-Ih1b5t3EEbrrHxeSfPsHtlO6J4OqA1jc5uuD3aNssUyLug==", "tag": "sQD8qgJoTrRoyQKPeCSBlQ=="}';
        [$unpacked_message, $sender_vk, $recip_vk] = Ed25519::unpack_message($packed, $verkey_recipient, $sigkey_recipient);
        $message = '{"content": "Test encryption \u0441\u0442\u0440\u043e\u043a\u0430"}';
        self::assertEquals($message, $unpacked_message);
        self::assertEquals($sender_vk, $verkey_sender);
        self::assertEquals($recip_vk, $verkey_recipient);
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     */
    public function test_crypto_sign(): void
    {
        [$verkey, $sigkey] = create_keypair(b'0000000000000000000000000000SEED');
        $msg = b'message';
        $signature = sign_message($msg, $sigkey);
        self::assertEquals('3tfqJYZ8ME8gTFUSHcH4uVTUx5kV7S1qPJJ65k2VtSocMfXvnzR1sbbfq6F2RcXrFtaufjEr4KQVu7aeyirYrcRm', bytes_to_b58($signature));

        $success = verify_signed_message($verkey, $msg, $signature);
        self::assertTrue($success);

        [$verkey2, $sigkey2] = create_keypair(b'000000000000000000000000000SEED2');
        self::assertNotEquals($verkey, $verkey2);
        $signature = sign_message($msg, $sigkey2);
        $success = verify_signed_message($verkey, $msg, $signature);
        self::assertFalse($success);
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     */
    public function test_did_from_verkey(): void
    {
        [$verkey,] = create_keypair(b'0000000000000000000000000000SEED');
        self::assertEquals('GXhavit2jGf2oT1sqMyvJtgJxNYPMHmTsdZ3c2ZYQLJExj', bytes_to_b58($verkey));
        $did = did_from_verkey($verkey);
        self::assertEquals('VVZbGvuFqBdoVNY1Jh4j9Q', bytes_to_b58($did));
    }
}
