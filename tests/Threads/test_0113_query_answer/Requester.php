<?php


namespace Siruis\Tests\Threads\test_0113_query_answer;


use Siruis\Agent\AriesRFC\feature_0113_question_answer\Messages\Question;
use Siruis\Agent\AriesRFC\feature_0113_question_answer\Recipes;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;

class Requester extends \Threaded
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
    private $req2resp;

    public $success;
    public $response_is_yes;

    /**
     * Requester constructor.
     * @param string $server_address
     * @param string $credentials
     * @param P2PConnection $p2p
     * @param $req2resp
     */
    public function __construct(string $server_address, string $credentials, P2PConnection $p2p, $req2resp)
    {
        $this->server_address = $server_address;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->req2resp = $req2resp;
    }

    public function work()
    {
        Hub::alloc_context($this->server_address, $this->credentials, $this->p2p);
        $query = new Question([], ['Yes', 'No'], 'Test question', 'Question detail');
        $query->setTtl(30);
        list($success, $answer) = Recipes::ask_and_wait_answer($query, $this->req2resp);
        $this->success = $success;
        $this->response_is_yes = $answer->getResponse() == 'Yes';
    }
}