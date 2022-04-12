<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0015_acks\Status;
use Siruis\Agent\AriesRFC\feature_0048_trust_ping\Ping;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;
use Siruis\Agent\AriesRFC\feature_0095_basic_message\Messages\Message as msg0095_Message;
use Siruis\Agent\AriesRFC\Mixins\Attach as msg0095_Attach;

class Test1Message extends Message {

}

class Test2Message extends Message {

}

class MessagesTest extends TestCase
{
    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public function test_type_parsing(): void
    {
        $str1 = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test-protocol/1.0/name';
        $type = Type::fromString($str1);
        self::assertEquals('did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/', $type->doc_uri);
        self::assertEquals('test-protocol', $type->protocol);
        self::assertEquals('name', $type->name);
        self::assertEquals('1.0', $type->version);
        self::assertEquals(1, $type->version_info->getMajor());
        self::assertEquals(0, $type->version_info->getMinor());
        self::assertEquals(0, $type->version_info->getPatch());

        $str2 = 'https://didcomm.org/test-protocol/1.2/name';
        $type = Type::fromString($str2);
        self::assertEquals('https://didcomm.org/', $type->doc_uri);
        self::assertEquals('test-protocol', $type->protocol);
        self::assertEquals('name', $type->name);
        self::assertEquals('1.2', $type->version);
        self::assertEquals(1, $type->version_info->getMajor());
        self::assertEquals(2, $type->version_info->getMinor());
        self::assertEquals(0, $type->version_info->getPatch());
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public function test_register_protocol_message_success(): void
    {
        Message::registerMessageClass(Test1Message::class, 'test-protocol');
        [$ok, $message] = Message::restoreMessageInstance([
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test-protocol/1.0/name'
        ]);

        self::assertTrue($ok);
        self::assertInstanceOf(Test1Message::class, $message);
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public function test_agnostic_doc_uri(): void
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
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public function test_register_protocol_message_fail(): void
    {
        Message::registerMessageClass(Test1Message::class, 'test-protocol');
        $array = Message::restoreMessageInstance([
            '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/fake-protocol/1.0/name'
        ]);

        self::assertFalse($array[0]);
        self::assertNull($array[1]);
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public function test_register_protocol_message_multiple_name(): void
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
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public function test_aries_ping_pong(): void
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
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_aries_ack(): void
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

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_attaches_mixin(): void
    {
        $msg = new msg0095_Message([], 'content', 'en');
        $attach = new msg0095_Attach('id', 'image/png', 'photo.png', null, null, 'eW91ciB0ZXh0');
        $msg->addAttach($attach);

        self::assertCount(1, $msg->getAttaches());
        self::assertInstanceOf(msg0095_Attach::class, $msg->getAttaches()[0]);
        self::assertEquals('eW91ciB0ZXh0', $msg->getAttaches()[0]->getData());
    }
}
