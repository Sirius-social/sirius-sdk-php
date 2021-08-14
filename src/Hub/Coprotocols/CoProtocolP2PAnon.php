<?php


namespace Siruis\Hub\Coprotocols;


use GuzzleHttp\Exception\GuzzleException;
use Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport;
use Siruis\Agent\Pairwise\TheirEndpoint;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Helpers\ArrayHelper;
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

    /**
     * @param Message $message
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws GuzzleException
     * @throws SiriusPendingOperation
     */
    public function send(Message $message)
    {
        $transport = $this->__get_transport_lazy();
        $this->__setup($message, false);
        $transport->send($message);
    }

    /**
     * @return array|Message|string|null
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusTimeoutIO
     */
    public function get_one()
    {
        $transport = $this->__get_transport_lazy();
        return $transport->get_one();
    }

    public function switch(Message $message): array
    {
        $transport = $this->__get_transport_lazy();
        list($success, $response) = $transport->switch($message);
        if (key_exists(CoProtocols::PLEASE_ACK_DECORATOR, $response)) {
            $this->thread_id = ArrayHelper::getValueWithKeyFromArray(
                'message_id', $response[CoProtocols::PLEASE_ACK_DECORATOR], $message->id
            );
        } else {
            $this->thread_id = null;
        }
        return [$success, $response];
    }

    public function __setup(Message $message, bool $please_ack = true)
    {
        $messageArray = json_decode($message->serialize());
        if ($please_ack) {
            if (!key_exists(CoProtocols::PLEASE_ACK_DECORATOR, $messageArray)) {
                $message->payload[CoProtocols::PLEASE_ACK_DECORATOR] = ['message_id' => $message->id];
            }
        }
        if ($this->thread_id) {
            $thread = ArrayHelper::getValueWithKeyFromArray(CoProtocols::THREAD_DECORATOR, $message->payload);
            if (!key_exists('thid', $thread)) {
                $thread['thid'] = $this->thread_id;
                $message->payload[CoProtocols::THREAD_DECORATOR] = $thread;
            }
        }
    }

    public function __get_transport_lazy(): ?TheirEndpointCoProtocolTransport
    {
        if (!$this->transport) {
            $this->hub = Hub::current_hub();
            $agent = $this->hub->get_agent_connection_lazy();
            $this->transport = $agent->spawnTheirEndpoint($this->my_verkey, $this->endpoint);
            $this->transport->start($this->protocols, $this->time_to_live);
            $this->is_start = true;
        }
        try {
            return $this->transport;
        } catch (SiriusConnectionClosed $e) {
            if ($this->is_aborted) {
                throw new OperationAbortedManually('User aborted operation');
            } else {
                throw new SiriusConnectionClosed('Errors: ' . $e);
            }
        }
    }
}