<?php


namespace Siruis\Tests\Threads\test_0113_query_answer;


use Siruis\Agent\AriesRFC\feature_0113_question_answer\Messages\Question;
use Siruis\Agent\AriesRFC\feature_0113_question_answer\Recipes;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;

class Responder extends \Threaded
{
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

    public $success;

    /**
     * Responder constructor.
     * @param string $server_address
     * @param string $credentials
     * @param P2PConnection $p2p
     */
    public function __construct(string $server_address, string $credentials, P2PConnection $p2p)
    {
        $this->server_address = $server_address;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
    }

    public function work()
    {
        Hub::alloc_context($this->server_address, $this->credentials, $this->p2p);
        $listener = Init::subscribe();
        $event = $listener->get_one();
        if ($event->getMessage() instanceof Question) {
            Recipes::make_answer('Yes', $event->getMessage(), $event->pairwise);
            $this->success = true;
        }
    }
}