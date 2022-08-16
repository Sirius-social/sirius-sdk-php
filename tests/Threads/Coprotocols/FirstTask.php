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
     * @var array
     */
    public $results;

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
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessage
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     */
    public function work(): void
    {
        $protocol = $this->reopenProtocol();
        $first_req = new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[0],
            'content' => 'Request1'
        ]);
        [$ok, $resp1] = $protocol->switch($first_req);
        [$ok, $resp2] = $protocol->switch(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[2],
            'content' => 'Request2'
        ]));
        $this->results = [$first_req, $resp1, $resp2];
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
        $protocol->restart();
        return $protocol;
    }
}