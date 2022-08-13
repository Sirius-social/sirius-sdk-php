<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Hub\Core\Hub;
use Siruis\Messaging\Message;

class CoProtocolThreadedP2P extends AbstractP2PCoProtocol
{
    private $thid, $to, $pthid;

    public function __construct(
        string $thid, Pairwise $to, string $pthid = null, int $time_to_live = null
    )
    {
        parent::__construct($time_to_live);
        $this->thid = $thid;
        $this->to = $to;
        $this->pthid = $pthid;
    }

    public function getThid(): string
    {
        return $this->thid;
    }

    public function getTo(): Pairwise
    {
        return $this->to;
    }

    public function getPthid(): ?string
    {
        return $this->pthid;
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\OperationAbortedManually
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     */
    public function send(Message $message): void
    {
        $transport = $this->get_transport_lazy();
        if ($transport) {
            $transport->send($message);
        }

        throw new OperationAbortedManually();
    }

    /**
     * @return array|null
     * @throws \Siruis\Errors\Exceptions\OperationAbortedManually
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_one(): ?array
    {
        $transport = $this->get_transport_lazy();
        if ($transport) {
            return $transport->get_one();
        }

        throw new OperationAbortedManually();
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\OperationAbortedManually
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessage
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function switch(Message $message): array
    {
        $transport = $this->get_transport_lazy();
        if ($transport) {
            return $transport->switch($message);
        }

        throw new OperationAbortedManually();
    }

    /**
     * @return ThreadBasedCoProtocolTransport|null
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_transport_lazy(): ?ThreadBasedCoProtocolTransport
    {
        if (!$this->transport) {
            $this->hub = Hub::current_hub();
            $agent = $this->hub->get_agent_connection_lazy();
            if (!$this->pthid) {
                $this->transport = $agent->spawnThidPairwise($this->thid, $this->to);
            } else {
                $this->transport = $agent->spawnThidPairwisePthd($this->thid, $this->to, $this->pthid);
            }
            $this->transport->start(null, $this->getTTL());
            $this->is_start = true;
        }
        return $this->transport;
    }
}