<?php


namespace Siruis\Tests\Threads\Coprotocols;


use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Hub\Coprotocols\AbstractCoProtocol;
use Siruis\Hub\Core\Hub;
use Siruis\Tests\CoprotocolsTest;
use Threaded;

class InfiniteReader extends Threaded
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

    public function __construct(AbstractCoProtocol $co, string $server_address, string $credentials, P2PConnection $p2p)
    {
        $this->co = $co;
        $this->server_address = $server_address;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
    }

    public function work()
    {
        try {
            Hub::alloc_context($this->server_address, $this->credentials, $this->p2p);
            while (true) {
                $msg = $this->co->get_one();
                print_r(json_encode($msg));
            }
        } catch (OperationAbortedManually $exception) {
            CoprotocolsTest::assertNotNull($exception);
            CoprotocolsTest::assertInstanceOf(OperationAbortedManually::class, $exception);
        }
    }
}