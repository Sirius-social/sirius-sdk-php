<?php

namespace Siruis\Tests\RPC;

use PHPUnit\Framework\TestCase;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Messaging\Message;
use Siruis\RPC\Futures\Future;
use SodiumException;
use Siruis\Tests\Helpers\Conftest;

class TestFuturePromise extends TestCase
{
    /**
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SodiumException
     */
    public function test_sane()
    {
        $p2p = Conftest::p2p();
        $agent_to_sdk = $p2p['agent']['tunnel'];
        $sdk_to_agent = $p2p['sdk']['tunnel'];

        $future = new Future($sdk_to_agent);
//        $future->getValue();
        $expected = 'Test OK';
        $promise_msg = new Message([
            '@type' => Future::MSG_TYPE,
            '@id' => 'promise-message-id',
            'is_tuple' => false,
            'is_bytes' => false,
            'value' => $expected,
            'exception' => null,
            '~thread' => [
                'thid' => $future->getPromise()['id']
            ]
        ]);

//        $ok = $future->wait(5);
//        self::assertFalse($ok);

        $agent_to_sdk->post($promise_msg);
        $ok  = $future->wait(5);
        self::assertTrue($ok);

        $actual = $future->getValue();
        self::assertEquals($expected, $actual);
    }
}
