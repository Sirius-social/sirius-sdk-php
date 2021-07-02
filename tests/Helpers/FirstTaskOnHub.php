<?php


namespace Siruis\Tests\Helpers;


use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Coprotocols\AbstractCoProtocol;
use Siruis\Hub\Core\Hub;
use Siruis\Messaging\Message;
use Threaded;

class FirstTaskOnHub extends Threaded
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
        $first_req = new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[0],
            'content' => 'Request1'
        ]);
        array_push(CoprotocolsTest::$MSG_LOG, $first_req);
        list($ok, $resp1) = $this->co->switch($first_req);
        CoprotocolsTest::assertTrue($ok);
        array_push(CoprotocolsTest::$MSG_LOG, $resp1);
        list($ok, $resp2) = $this->co->switch(new Message([
            '@type' => CoprotocolsTest::$TEST_MSG_TYPES[2],
            'content' => 'Request2'
        ]));
        CoprotocolsTest::assertTrue($ok);
        array_push(CoprotocolsTest::$MSG_LOG, $resp2);
    }
}