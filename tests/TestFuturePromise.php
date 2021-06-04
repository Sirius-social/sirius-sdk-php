<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusPromiseContextException;
use Siruis\Errors\Exceptions\SiriusValueEmpty;
use Siruis\Errors\IndyExceptions\ErrorCode;
use Siruis\Errors\IndyExceptions\WalletItemAlreadyExists;
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

        $agent_to_sdk->post($promise_msg);
        $ok  = $future->wait(5);
        self::assertTrue($ok);

        $actual = $future->getValue();
        self::assertEquals($expected, $actual);
    }

    /**
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SodiumException
     * @throws SiriusValueEmpty
     */
    public function test_set_non_indy_error()
    {
        $p2p = Conftest::p2p();
        $agent_to_sdk = $p2p['agent']['tunnel'];
        $sdk_to_agent = $p2p['sdk']['tunnel'];

        $future = new Future($sdk_to_agent);

        $exc = new RuntimeException('test error message');

        $promise_msg = new Message([
            '@type' => Future::MSG_TYPE,
            '@id' => 'promise-message-id',
            'is_tuple' => false,
            'is_bytes' => false,
            'value' => null,
            'exception' => [
                'indy' => null,
                'class_name' => get_class($exc),
                'printable' => $exc->getMessage()
            ],
            '~thread' => [
                'thid' => $future->getPromise()['id']
            ]
        ]);

        $agent_to_sdk->post($promise_msg);
        $ok = $future->wait(5);
        self::assertTrue($ok);
        self::assertTrue($future->hasException());

        $fut_exc = $future->getException();

        self::assertNotNull($fut_exc);
        self::assertInstanceOf(SiriusPromiseContextException::class, $fut_exc);
        self::assertEquals('test error message', $fut_exc->printable);
        self::assertEquals('RuntimeException', $fut_exc->class_name);
    }

    /**
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SiriusPromiseContextException
     * @throws SiriusValueEmpty
     * @throws SodiumException
     */
    public function test_set_indy_error()
    {
        $p2p = Conftest::p2p();
        $agent_to_sdk = $p2p['agent']['tunnel'];
        $sdk_to_agent = $p2p['sdk']['tunnel'];

        $future = new Future($sdk_to_agent);

        $exc = new WalletItemAlreadyExists(
            ErrorCode::WalletItemAlreadyExists,
            ['message' => 'test error message', 'indy_backtrace' => '']
        );

        $promise_msg = new Message([
            '@type' => Future::MSG_TYPE,
            '@id' => 'promise-message-id',
            'is_tuple' => false,
            'is_bytes' => false,
            'value' => null,
            'exception' => [
                'indy' => [
                    'error_code' => $exc->error_code,
                    'message' => $exc->message
                ],
                'class_name' => get_class($exc),
                'printable' => $exc->getMessage()
            ],
            '~thread' => [
                'thid' => $future->getPromise()['id']
            ]
        ]);

        $agent_to_sdk->post($promise_msg);
        $ok = $future->wait(5);
        self::assertTrue($ok);
        self::assertTrue($future->hasException());

        $fut_exc = $future->getException();
        self::assertNotNull($fut_exc);
        self::assertInstanceOf(WalletItemAlreadyExists::class, $fut_exc);
        self::assertEquals('test error message', $fut_exc->getMessage());
    }

    /**
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SodiumException
     */
    public function test_tuple_value()
    {
        $p2p = Conftest::p2p();
        $agent_to_sdk = $p2p['agent']['tunnel'];
        $sdk_to_agent = $p2p['sdk']['tunnel'];

        $future = new Future($sdk_to_agent);

        $expected = [1, 2, 'value'];
        $promise_msg = new Message([
            '@type' => Future::MSG_TYPE,
            '@id' => 'promise-message-id',
            'is_tuple' => true,
            'is_bytes' => false,
            'value' => $expected,
            'exception' => null,
            '~thread' => [
                'thid' => $future->getPromise()['id']
            ]
        ]);

        $agent_to_sdk->post($promise_msg);
        $ok = $future->wait(5);
        self::assertTrue($ok);
        $actual = $future->getValue();
        self::assertEquals($expected, $actual);
    }

    /**
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SodiumException
     */
    public function test_bytes_value()
    {
        $p2p = Conftest::p2p();
        $agent_to_sdk = $p2p['agent']['tunnel'];
        $sdk_to_agent = $p2p['sdk']['tunnel'];

        $future = new Future($sdk_to_agent);
        $expected = b'Hello!';
        $promise_msg = new Message([
            '@type' => Future::MSG_TYPE,
            '@id' => 'promise-message-id',
            'is_tuple' => false,
            'is_bytes' => true,
            'value' => Encryption::bytes_to_b64($expected),
            'exception' => null,
            '~thread' => [
                'thid' => $future->getPromise()['id']
            ]
        ]);

        $agent_to_sdk->post($promise_msg);
        $ok = $future->wait(5);
        self::assertTrue($ok);
        $actual = $future->getValue();
        self::assertEquals($expected, $actual);
    }
}
