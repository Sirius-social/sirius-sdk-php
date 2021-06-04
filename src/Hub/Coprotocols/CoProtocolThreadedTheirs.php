<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Hub\Core\Hub;
use Siruis\Messaging\Message;

class CoProtocolThreadedTheirs extends AbstractCoProtocol
{
    /**
     * @var string
     */
    private $thid;
    /**
     * @var array
     */
    public $theirs;
    /**
     * @var string|null
     */
    private $pthid;
    protected $dids;

    public function __construct(string $thid, array $theirs, string $pthid = null, int $time_to_live = null)
    {
        parent::__construct($time_to_live);
        if (count($theirs) < 1) {
            throw new SiriusContextError('theirs is empty');
        }
        $this->thid = $thid;
        $this->theirs = $theirs;
        $this->pthid = $pthid;
        $dids = [];
        foreach ($theirs as $their) {
            array_push($dids, $their->their->did);
        }
        $this->dids = $dids;
    }

    /**
     * Send message to given participants
     *
     * @param Message $message
     * @return array List[( str: participant-id, bool: message was successfully sent, str: endpoint response body )]
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     * @throws SiriusPendingOperation
     */
    public function send(Message $message): array
    {
        $results = [];
        $transport = $this->__get_transport_lazy();
        $responses = $transport->send_many($message, $this->theirs);
        foreach ($this->theirs as $p2p) {
            foreach ($responses as $response) {
                $results[$p2p] = [$response[0], $response[1]];
            }
        }
        return $results;
    }

    /**
     * Read event from any of participants at given timeout
     *
     * @return array|null[] (Pairwise: participant-id, Message: message from given participant)
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function get_one(): array
    {
        $transport = $this->__get_transport_lazy();
        try {
            $get_one = $transport->get_one();
        } catch (SiriusTimeoutIO $exception) {
            return [null, null];
        }
        $p2p = $this->__load_p2p_from_verkey($get_one[1]);
        return [$p2p, $get_one[0]];
    }

    /**
     * Switch state while participants at given timeout give responses
     *
     * @param Message $message
     * @return array {
     *      Pairwise: participant,
     *      (
     *          bool: message was successfully sent to participant,
     *          Message: response message from participant or Null if request message was not successfully sent
     *      )
     * }
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     */
    public function switch(Message $message): array
    {
        $statuses = $this->send($message);
        // fill errors to result just now
        $results = [];
        foreach ($statuses as $p2p => $status) {
            if ($status[0]) {
                $results[$p2p] = [false, null];
            }
        }
        // then work with success participants only
        $success_theirs = [];
        foreach ($statuses as $p2p => $status) {
            if ($status[0]) {
                $success_theirs[$p2p] = [false, null];
            }
        }
        $accum = 0;
        while ($accum < count($success_theirs)) {
            $get_one = $this->get_one();
            $p2p = $get_one[0];
            $message = $get_one[1];
            if (!$p2p) {
                break;
            }
            if (in_array($p2p->their->did, $this->dids)) {
                $success_theirs[$p2p] = [true, $message];
                $accum += 1;
            }
        }
        array_push($results, $success_theirs);
        return $results;
    }

    /**
     * @param string $verkey
     * @return Pairwise|null
     */
    protected function __load_p2p_from_verkey(string $verkey): ?Pairwise
    {
        foreach ($this->theirs as $p2p) {
            if ($p2p->their->verkey == $verkey) {
                return $p2p;
            }
        }
        return null;
    }

    /**
     * @return ThreadBasedCoProtocolTransport|null
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     */
    protected function __get_transport_lazy(): ?ThreadBasedCoProtocolTransport
    {
        if (!$this->transport) {
            $agent = Hub::current_hub()->get_agent_connection_lazy();
            if (!$this->pthid) {
                $this->transport = $agent->spawnThid($this->thid);
            } else {
                $this->transport = $agent->spawnThidPthid($this->thid, $this->pthid);
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