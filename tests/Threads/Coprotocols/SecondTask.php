<?php


namespace Siruis\Tests\Threads\Coprotocols;


use Siruis\Agent\Coprotocols\AbstractCoProtocolTransport;
use Siruis\Messaging\Message;
use Siruis\Tests\CoprotocolsTest;
use Threaded;
use Volatile;

class SecondTask extends Threaded
{
    protected $protocol;

    public function __construct(AbstractCoProtocolTransport $protocol)
    {
        $this->protocol = $protocol;
    }

    public function work()
    {
        $protocol = $this->getProtocol();
        list($ok, $resp1) = $protocol->switch(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[1],
            'content' => 'Response1'
        ]));
        CoprotocolsTest::assertTrue($ok);
        array_push(CoprotocolsTest::$MSG_LOG, $resp1);
        $protocol->send(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[3],
            'content' => 'End'
        ]));
    }

    protected function getProtocol()
    {
        $protocol = $this->protocol;
        $protocol->rpc->reopen();
        return $protocol;
    }
}