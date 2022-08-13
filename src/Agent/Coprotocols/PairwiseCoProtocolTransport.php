<?php

namespace Siruis\Agent\Coprotocols;

use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Pairwise\Pairwise;

class PairwiseCoProtocolTransport extends AbstractCoProtocolTransport
{
    /**
     * @var \Siruis\Agent\Pairwise\Pairwise
     */
    private $pairwise;

    /**
     * PairwiseCoProtocolTransport constructor.
     *
     * @param \Siruis\Agent\Pairwise\Pairwise $pairwise
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
    public function __construct(Pairwise $pairwise, AgentRPC $rpc)
    {
        parent::__construct($rpc);
        $this->pairwise = $pairwise;
        $this->_setup(
            $pairwise->their->verkey,
            $pairwise->their->endpoint,
            $pairwise->me->verkey,
            $pairwise->their->routing_keys
        );
    }

    /**
     * @param string[] $protocols
     * @param int|null $time_to_live
     *
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function start(array $protocols, int $time_to_live = null)
    {
        parent::start($protocols, $time_to_live);
        $this->rpc->start_protocol_for_p2p(
            $this->pairwise->me->verkey,
            $this->pairwise->their->verkey,
            $this->getProtocols(),
            $time_to_live
        );
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function stop()
    {
        parent::stop();
        $this->rpc->stop_protocol_for_p2p(
            $this->pairwise->me->verkey,
            $this->pairwise->their->verkey,
            $this->getProtocols(),
            true
        );
    }
}