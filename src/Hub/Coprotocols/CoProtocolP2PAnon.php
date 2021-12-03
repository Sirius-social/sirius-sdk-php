<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport;
use Siruis\Agent\Pairwise\TheirEndpoint;
use Siruis\Errors\Exceptions\OperationAbortedManually;
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


    /**
     * CoProtocolP2PAnon constructor.
     * @param string $my_verkey
     * @param \Siruis\Agent\Pairwise\TheirEndpoint $endpoint
     * @param array $protocols
     * @param int|null $time_to_live
     */
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
     * @param \Siruis\Messaging\Message $message
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\OperationAbortedManually
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function send(Message $message): void
    {
        $transport = $this->get_transport_lazy();
        $this->setup($message, false);
        if ($transport) {
            $transport->send($message);
        } else {
            throw new OperationAbortedManually('Transport null');
        }
    }

    /**
     * @return array|false|null
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_one()
    {
        $transport = $this->get_transport_lazy();
        if ($transport) {
            return $transport->get_one();
        }

        return false;
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\OperationAbortedManually
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function switch(Message $message): array
    {
        $transport = $this->get_transport_lazy();
        if ($transport) {
            [$success, $response] = $transport->switch($message);
            if (array_key_exists(CoProtocols::PLEASE_ACK_DECORATOR, $response)) {
                $this->thread_id = ArrayHelper::getValueWithKeyFromArray(
                    'message_id', $response[CoProtocols::PLEASE_ACK_DECORATOR], $message->id
                );
            } else {
                $this->thread_id = null;
            }
            return [$success, $response];
        }

        throw new OperationAbortedManually('Transport null');
    }

    /**
     * @throws \JsonException
     */
    public function setup(Message $message, bool $please_ack = true): void
    {
        $messageArray = json_decode($message->serialize(), false, 512, JSON_THROW_ON_ERROR);
        if ($please_ack && !array_key_exists(CoProtocols::PLEASE_ACK_DECORATOR, $messageArray)) {
            $message->payload[CoProtocols::PLEASE_ACK_DECORATOR] = ['message_id' => $message->id];
        }
        if ($this->thread_id) {
            $thread = ArrayHelper::getValueWithKeyFromArray(CoProtocols::THREAD_DECORATOR, $message->payload);
            if (!array_key_exists('thid', $thread)) {
                $thread['thid'] = $this->thread_id;
                $message->payload[CoProtocols::THREAD_DECORATOR] = $thread;
            }
        }
    }

    /**
     * @return \Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport|null
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_transport_lazy(): ?TheirEndpointCoProtocolTransport
    {
        if (!$this->transport) {
            $this->hub = Hub::current_hub();
            $agent = $this->hub->get_agent_connection_lazy();
            $this->transport = $agent->spawnTheirEndpoint($this->my_verkey, $this->endpoint);
            $this->transport->start($this->protocols, $this->time_to_live);
            $this->is_start = true;
        }
        return $this->transport;
    }
}