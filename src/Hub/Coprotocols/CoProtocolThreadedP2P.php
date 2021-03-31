<?php


namespace Siruis\Hub\Coprotocols;


use GuzzleHttp\Exception\GuzzleException;
use Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Hub\Core\Hub;
use Siruis\Messaging\Message;

class CoProtocolThreadedP2P extends AbstractP2PCoProtocol
{
    /**
     * @var string
     */
    public $thid;
    /**
     * @var Pairwise
     */
    public $to;
    /**
     * @var string|null
     */
    public $pthid;

    public function __construct(
        string $thid, Pairwise $to, string $pthid = null, int $time_to_live = null
    )
    {
        parent::__construct($time_to_live);
        $this->thid = $thid;
        $this->to = $to;
        $this->pthid = $pthid;
    }

    /**
     * @param Message $message
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     * @throws GuzzleException
     * @throws SiriusPendingOperation
     */
    public function send(Message $message)
    {
        $transport = $this->__get_transport_lazy();
        $transport->send($message);
    }

    /**
     * @return array|Message|string|null
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusTimeoutIO
     */
    public function get_one()
    {
        $transport = $this->__get_transport_lazy();
        return $transport->get_one();
    }

    /**
     * @param Message $message
     * @return array
     * @throws GuzzleException
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     */
    public function switch(Message $message): array
    {
        $transport = $this->__get_transport_lazy();
        return $transport->switch($message);
    }

    /**
     * @return ThreadBasedCoProtocolTransport|null
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     */
    public function __get_transport_lazy(): ?ThreadBasedCoProtocolTransport
    {
        if (!$this->transport) {
            $this->hub = Hub::current_hub();
            $agent = $this->hub->get_agent_connection_lazy();
            if (!$this->pthid) {
                $this->transport = $agent->spawnThidPairwise($this->thid, $this->to);
            } else {
                $this->transport = $agent->spawnThidPairwisePthd($this->thid, $this->to, $this->pthid);
            }
            $this->transport->start(null, $this->time_to_live);
            $this->is_start = true;
        }
        try {
            return $this->transport;
        } catch (SiriusConnectionClosed $exception) {
            if ($this->is_aborted) {
                throw new OperationAbortedManually('User aborted operation');
            } else {
                throw new SiriusConnectionClosed('Errors: ' . $exception->getMessage());
            }
        }
    }
}