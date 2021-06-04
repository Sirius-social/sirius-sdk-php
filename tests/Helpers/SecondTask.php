<?php


namespace Siruis\Tests\Helpers;


use Siruis\Agent\Coprotocols\AbstractCoProtocolTransport;
use Siruis\Messaging\Message;
use Siruis\Tests\CoprotocolsTest;
use Threaded;

class SecondTask extends Threaded
{
    /**
     * @var AbstractCoProtocolTransport
     */
    private $protocol;

    /**
     * SecondThread constructor.
     * @param AbstractCoProtocolTransport $protocol
     */
    public function __construct(AbstractCoProtocolTransport $protocol)
    {
        $this->protocol = $protocol;
    }

    public function work()
    {
        list($ok, $resp1) = $this->protocol->switch(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[1],
            'content' => 'Response1'
        ]));
        CoprotocolsTest::assertTrue($ok);
        array_push(CoprotocolsTest::$MSG_LOG, $resp1);
        $this->protocol->send(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[3],
            'content' => 'End'
        ]));
    }
}