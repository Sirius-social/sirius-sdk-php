<?php


namespace Siruis\Tests\Threads\Coprotocols;


use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Coprotocols\AbstractCoProtocol;
use Siruis\Hub\Core\Hub;
use Siruis\Messaging\Message;
use Siruis\Tests\CoprotocolsTest;
use Threaded;

class SecondTaskOnHub extends Threaded
{
    /**
     * @var AbstractCoProtocol
     */
    private $co;
    /**
     * @var string
     */
    private $server_address;
    /**
     * @var string
     */
    private $credentials;
    /**
     * @var P2PConnection
     */
    private $p2p;

    public function __construct(
        AbstractCoProtocol $co, string $server_address, string $credentials, P2PConnection $p2p
    )
    {
        $this->co = $co;
        $this->server_address = $server_address;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
    }

    public function work()
    {
        Hub::alloc_context($this->server_address, $this->credentials, $this->p2p);
        list($ok, $resp1) = $this->co->switch(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[1],
            'content' => 'Response1'
        ]));
        CoprotocolsTest::assertTrue($ok);
        array_push(CoprotocolsTest::$MSG_LOG, $resp1);
        $this->co->send(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[3],
            'content' => 'End'
        ]));
    }
}