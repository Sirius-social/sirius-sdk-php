<?php


namespace Siruis\Agent\Coprotocols;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Pairwise\TheirEndpoint;

class TheirEndpointCoProtocolTransport extends AbstractCoProtocolTransport
{
    public $endpoint;
    public $my_verkey;

    public function __construct(string $my_verkey, TheirEndpoint $endpoint, AgentRPC $rpc)
    {
        parent::__construct($rpc);
        $this->my_verkey = $my_verkey;
        $this->endpoint = $endpoint;
        $this->setup(
            $endpoint->verkey,
            $endpoint->endpoint,
            $my_verkey,
            $endpoint->routing_keys
        );
    }

    public function start(array $protocols = null, int $time_to_live = null)
    {
        parent::start($protocols, $time_to_live);
        $this->rpc->start_protocol_for_p2p(
            $this->my_verkey,
            $this->endpoint->verkey,
            $this->protocols,
            $time_to_live
        );
    }

    public function stop()
    {
        parent::stop();
        $this->rpc->stop_protocol_for_p2p(
            $this->my_verkey,
            $this->endpoint->verkey,
            $this->protocols,
            true
        );
    }
}