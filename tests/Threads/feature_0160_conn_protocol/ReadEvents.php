<?php


namespace Siruis\Tests\Threads\feature_0160_conn_protocol;


use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;

class ReadEvents extends \Threaded
{
    /**
     * @var string
     */
    private $uri;
    /**
     * @var string
     */
    private $credentials;
    /**
     * @var P2PConnection
     */
    private $p2p;

    /**
     * ReadEvents constructor.
     * @param string $uri
     * @param string $credentials
     * @param P2PConnection $p2p
     */
    public function __construct(string $uri, string $credentials, P2PConnection $p2p)
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $listener = Init::subscribe();
        $event = $listener->get_one();
        error_log('========= EVENT ============');
        error_log(json_encode($event->payload, JSON_UNESCAPED_SLASHES));
        error_log('============================');
    }
}