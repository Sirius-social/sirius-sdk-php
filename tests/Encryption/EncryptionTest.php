<?php

namespace Siruis\Tests\Encryption;

use Siruis\Encryption\Ed25519;
use PHPUnit\Framework\TestCase;

class EncryptionTest extends TestCase
{

    public function testBytes_to_b64()
    {
        $dd = Ed25519::encrypt_plaintext('message', 'add', 'key');
    }

    public function testB64_to_bytes()
    {

    }

    public function testB58_to_bytes()
    {

    }

    public function testCreate_keypair()
    {

    }

    public function testBytes_to_b58()
    {

    }
}
