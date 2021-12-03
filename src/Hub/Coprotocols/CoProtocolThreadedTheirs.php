<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\SiriusContextError;
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

    /**
     * CoProtocolThreadedTheirs constructor.
     * @param string $thid
     * @param array $theirs
     * @param string|null $pthid
     * @param int|null $time_to_live
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     */
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
            $dids[] = $their->their->did;
        }
        $this->dids = $dids;
    }

    /**
     * Send message to given participants
     *
     * @param \Siruis\Messaging\Message $message
     * @return array List[( str: participant-id, bool: message was successfully sent, str: endpoint response body )]
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\OperationAbortedManually
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function send(Message $message): array
    {
        $results = [];
        $transport = $this->get_transport_lazy();
        if ($transport) {
            $responses = $transport->send_many($message, $this->theirs);

            foreach ($this->theirs as $p2p) {
                foreach ($responses as $response) {
                    $results[$p2p] = [$response[0], $response[1]];
                }
            }

            return $results;
        }

        throw new OperationAbortedManually();
    }

    /**
     * Read event from any of participants at given timeout
     *
     * @return array|null[] (Pairwise: participant-id, Message: message from given participant)
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\OperationAbortedManually
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_one(): array
    {
        $transport = $this->get_transport_lazy();
        if ($transport) {
            [$message, $sender_verkey] = $transport->get_one();
            $p2p = $this->load_p2p_from_verkey($sender_verkey);
            return [$p2p, $message];
        }

        throw new OperationAbortedManually();
    }

    /**
     * Switch state while participants at given timeout give responses
     * @param \Siruis\Messaging\Message $message
     * @return array
     * {
     *      Pairwise: participant,
     *      (
     *          bool: message was successfully sent to participant,
     *          Message: response message from participant or Null if request message was not successfully sent
     *      )
     * }
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\OperationAbortedManually
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
            [$p2p, $message] = $this->get_one();
            if (!$p2p) {
                break;
            }
            if (in_array($p2p->their->did, $this->dids, true)) {
                $success_theirs[$p2p] = [true, $message];
                ++$accum;
            }
        }
        $results[] = $success_theirs;
        return $results;
    }


    protected function load_p2p_from_verkey(string $verkey): ?Pairwise
    {
        foreach ($this->theirs as $p2p) {
            if ($p2p->their->verkey === $verkey) {
                return $p2p;
            }
        }
        return null;
    }

    /**
     * @return \Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport|null
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    protected function get_transport_lazy(): ?ThreadBasedCoProtocolTransport
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
        return $this->transport;
    }
}