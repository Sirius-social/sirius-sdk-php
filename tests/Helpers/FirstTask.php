<?php


namespace Siruis\Tests\Helpers;


use Siruis\Agent\Coprotocols\AbstractCoProtocolTransport;
use Siruis\Messaging\Message;
use Siruis\Tests\CoprotocolsTest;
use Threaded;
use Volatile;

class FirstTask extends Volatile
{
    /**
     * @var AbstractCoProtocolTransport
     */
    public $shared;

    public function __construct(AbstractCoProtocolTransport $protocol)
    {
        $this->shared = new Volatile();
        $this->shared['protocol'] = $protocol;
    }

    public function work()
    {
        $protocol = $this->getProtocol();
        $first_req = new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[0],
            'content' => 'Request1'
        ]);
        array_push(CoprotocolsTest::$MSG_LOG, $first_req);
        list($ok, $resp1) = $protocol->switch($first_req);
        CoprotocolsTest::assertTrue($ok);
        array_push(CoprotocolsTest::$MSG_LOG, $resp1);
        list($ok, $resp2) = $protocol->switch(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[2],
            'content' => 'Request2'
        ]));
        CoprotocolsTest::assertTrue($ok);
        array_push(CoprotocolsTest::$MSG_LOG, $resp2);
    }

    public function getProtocol()
    {
        /** @var AbstractCoProtocolTransport $protocol */
        $protocol = $this->shared['protocol'];
        $protocol->rpc->reopen();
        return $protocol;
    }
}