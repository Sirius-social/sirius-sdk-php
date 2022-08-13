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
    public $resp1;

    public function __construct(AbstractCoProtocolTransport $protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * Fire.
     *
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessage
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     */
    public function work()
    {
        $protocol = $this->reopenProtocol();
        [$ok, $resp1] = $protocol->switch(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[1],
            'content' => 'Response1'
        ]));
        $protocol->send(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[3],
            'content' => 'End'
        ]));
        $this->resp1 = $resp1;
    }

    /**
     * Reopen protocol property.
     *
     * @return \Siruis\Agent\Coprotocols\AbstractCoProtocolTransport
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     */
    public function reopenProtocol(): AbstractCoProtocolTransport
    {
        $protocol = $this->protocol;
        if (!$protocol->getRPC()->isOpen()) {
            $protocol->getRPC()->reopen();
        }
        return $protocol;
    }
}