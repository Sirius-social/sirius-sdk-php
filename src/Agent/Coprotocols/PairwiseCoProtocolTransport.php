<?php


namespace Siruis\Agent\Coprotocols;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Pairwise\Pairwise;

class PairwiseCoProtocolTransport extends AbstractCoProtocolTransport
{
    /**
     * @var Pairwise
     */
    public $pairwise;

    public function __construct(Pairwise $pairwise, AgentRPC $rpc)
    {
        parent::__construct($rpc);
        $this->pairwise = $pairwise;
        $this->setup(
            $pairwise->their->verkey,
            $pairwise->their->endpoint,
            $pairwise->me->verkey,
            $pairwise->their->routing_keys
        );
    }

    public function start(array $protocols, int $time_to_live = null)
    {
        parent::start($protocols, $time_to_live);
        $this->rpc->start_protocol_for_p2p(
            $this->pairwise->me->verkey,
            $this->pairwise->their->verkey,
            $this->protocols,
            $time_to_live
        );
    }

    public function stop()
    {
        parent::stop();
        $this->rpc->stop_protocol_for_p2p(
            $this->pairwise->me->verkey,
            $this->pairwise->their->verkey,
            $this->protocols,
            true
        );
    }
}