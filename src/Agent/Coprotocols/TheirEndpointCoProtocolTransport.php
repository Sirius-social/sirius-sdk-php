<?php


namespace Siruis\Agent\Coprotocols;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Pairwise\TheirEndpoint;

class TheirEndpointCoProtocolTransport extends AbstractCoProtocolTransport
{
    /**
     * @var \Siruis\Agent\Pairwise\TheirEndpoint
     */
    public $endpoint;
    /**
     * @var string
     */
    public $my_verkey;

    /**
     * TheirEndpointCoProtocolTransport constructor.
     * @param string $my_verkey
     * @param \Siruis\Agent\Pairwise\TheirEndpoint $endpoint
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     */
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

    /**
     * @param array|null $protocols
     * @param int|null $time_to_live
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function start(array $protocols = null, int $time_to_live = null): void
    {
        parent::start($protocols, $time_to_live);
        $this->rpc->start_protocol_for_p2p(
            $this->my_verkey,
            $this->endpoint->verkey,
            $this->protocols,
            $time_to_live
        );
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function stop(): void
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