<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Agent\Pairwise\TheirEndpoint;
use Siruis\Hub\Core\Hub;
use Siruis\Messaging\Message;

class CoProtocolP2PAnon extends AbstractP2PCoProtocol
{
    /**
     * @var string
     */
    public $my_verkey;
    /**
     * @var TheirEndpoint
     */
    public $endpoint;
    /**
     * @var array
     */
    public $protocols;
    public $thread_id;


    public function __construct(string $my_verkey,
                                TheirEndpoint $endpoint,
                                array $protocols,
                                int $time_to_live = null)
    {
        parent::__construct($time_to_live);
        $this->my_verkey = $my_verkey;
        $this->endpoint = $endpoint;
        $this->protocols = $protocols;
        $this->thread_id = null;
    }

    public function send(Message $message)
    {

    }

    /**
     * @inheritDoc
     */
    public function get_one()
    {
        // TODO: Implement get_one() method.
    }

    public function switch(Message $message): array
    {
        // TODO: Implement switch() method.
    }

    public function __get_transport_lazy()
    {

    }
}