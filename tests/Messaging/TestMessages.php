<?php

namespace Siruis\Tests\Messaging;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0015_acks\Status;
use Siruis\Agent\AriesRFC\feature_0048_trust_ping\Ping;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;

class Test1Message extends Message {

}

class Test2Message extends Message {

}

class TestMessages extends TestCase
{
    /** @test
     * @throws SiriusInvalidType
     */
    public function test_type_parsing()
    {
        $str1 = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test-protocol/1.0/name';
        $type = Type::fromString($str1);
        self::assertEquals('did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/', $type->doc_uri);
        self::assertEquals('test-protocol', $type->protocol);
        self::assertEquals('name', $type->name);
        self::assertEquals('1.0', $type->version);
        self::assertEquals(1, $type->version_info->major);
        self::assertEquals(0, $type->version_info->minor);
        self::assertEquals(0, $type->version_info->patch);

        $str2 = 'https://didcomm.org/test-protocol/1.2/name';
        $type = Type::fromString($str2);
        self::assertEquals('https://didcomm.org/', $type->doc_uri);
        self::assertEquals('test-protocol', $type->protocol);
        self::assertEquals('name', $type->name);
        self::assertEquals('1.2', $type->version);
        self::assertEquals(1, $type->version_info->major);
        self::assertEquals(2, $type->version_info->minor);
        self::assertEquals(0, $type->version_info->patch);
    }

    /** @test
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function test_register_protocol_message_success()
    {
        Message::registerMessageClass(Test1Message::class, 'test-protocol');
        $array = Message::restoreMessageInstance([
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test-protocol/1.0/name'
        ]);

        self::assertTrue($array[0]);
        self::assertInstanceOf(Test1Message::class, $array[1]);
    }

    /**
     * @test
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function test_agnostic_doc_uri()
    {
        Message::registerMessageClass(Test1Message::class, 'test-protocol');
        $array = Message::restoreMessageInstance([
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test-protocol/1.0/name'
        ]);

        self::assertTrue($array[0]);
        self::assertInstanceOf(Test1Message::class, $array[1]);

        $arrayTwo = Message::restoreMessageInstance([
            '@type' => 'https://didcomm.org/test-protocol/1.0/name'
        ]);

        self::assertTrue($arrayTwo[0]);
        self::assertInstanceOf(Test1Message::class, $arrayTwo[1]);
    }

    /**
     * @test
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function test_register_protocol_message_fail()
    {
        Message::registerMessageClass(Test1Message::class, 'test-protocol');
        $array = Message::restoreMessageInstance([
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/fake-protocol/1.0/name'
        ]);

        self::assertFalse($array[0]);
        self::assertNull($array[1]);
    }

    /**
     * @test
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function test_register_protocol_message_multiple_name()
    {
        Message::registerMessageClass(Test1Message::class, 'test-protocol');
        Message::registerMessageClass(Test2Message::class, 'test-protocol', 'test-name');

        $arrayOne = Message::restoreMessageInstance([
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test-protocol/1.0/name'
        ]);

        self::assertTrue($arrayOne[0]);
        self::assertInstanceOf(Test1Message::class, $arrayOne[1]);

        $arrayTwo = Message::restoreMessageInstance([
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test-protocol/1.0/test-name'
        ]);

        self::assertTrue($arrayTwo[0]);
        self::assertInstanceOf(Test2Message::class, $arrayTwo[1]);
    }

    /**
     * @test
     * @throws SiriusInvalidType
     * @throws SiriusInvalidMessageClass
     */
    public function test_aries_ping_pong()
    {
        $pingPayload = [
            '@id' => 'trust-ping-message-id',
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/trust_ping/1.0/ping',
            "comment" => "Hi. Are you OK?",
            "response_requested" => True
        ];
        Message::registerMessageClass(Ping::class, 'trust_ping');
        $pingArray = Message::restoreMessageInstance($pingPayload);
        $ping = $pingArray[1];
        self::assertTrue($pingArray[0]);
        self::assertInstanceOf(Ping::class, $ping);
        self::assertEquals('Hi. Are you OK?', $ping->getComment());
        self::assertTrue($ping->getResponseRequested());
    }

    /**
     * @test
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     */
    public function test_aries_ack()
    {
        $payload = [
            '@id' => 'ack-message-id',
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/notification/1.0/ack',
            'status' => 'PENDING',
            '~thread' => [
                'thid' => 'thread-id'
            ]
        ];
        $message = new Ack($payload,null, null, null, 'ack-thread-id', new Status(Status::PENDING));

        self::assertEquals('notification', $message->getProtocol());
        self::assertEquals('ack', $message->getName());
        self::assertEquals('1.0', $message->getVersion());
        self::assertEquals('1.0.0', (string)$message->getVersionInfo());
        self::assertEquals(Status::PENDING, $message->getStatus());
        $message->validate();

        Message::registerMessageClass(Ack::class, 'notification');
        $restored = Message::restoreMessageInstance($payload);
        $ack = $restored[1];
        self::assertTrue($restored[0]);
        self::assertInstanceOf(Ack::class, $ack);
        self::assertEquals('thread-id', $ack->getThreadId());
        $ack->validate();
        self::assertEquals(Status::PENDING, $ack->getStatus());
    }
}
