<?php


namespace Siruis\Tests\Threads\Coprotocols;


use Siruis\Agent\Coprotocols\AbstractCoProtocolTransport;
use Siruis\Messaging\Message;
use Siruis\Tests\CoprotocolsTest;
use Threaded;
use function PHPUnit\Framework\assertTrue;

class FirstTask extends Threaded
{
    /**
     * @var \Siruis\Agent\Coprotocols\AbstractCoProtocolTransport
     */
    protected $protocol;

    /**
     * FirstTask constructor.
     * @param \Siruis\Agent\Coprotocols\AbstractCoProtocolTransport $protocol
     */
    public function __construct(AbstractCoProtocolTransport $protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * Fire.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function work(): void
    {
        $protocol = $this->reopenProtocol();
        $first_req = new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[0],
            'content' => 'Request1'
        ]);
        CoprotocolsTest::$MSG_LOG[] = $first_req;
        [$ok, $resp1] = $protocol->switch($first_req);
        assertTrue($ok);
        CoprotocolsTest::$MSG_LOG[] = $resp1;
        [$ok, $resp2] = $protocol->switch(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[2],
            'content' => 'Request2'
        ]));
        assertTrue($ok);
        CoprotocolsTest::$MSG_LOG[] = $resp2;
    }


    /**
     * Reopen protocol property.
     *
     * @return \Siruis\Agent\Coprotocols\AbstractCoProtocolTransport
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function reopenProtocol(): AbstractCoProtocolTransport
    {
        $protocol = $this->protocol;
        if (!$protocol->rpc->isOpen()) {
            $protocol->rpc->reopen();
        }
        return $protocol;
    }
}