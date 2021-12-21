<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Helpers\ArrayHelper;
use Siruis\Hub\Core\Hub;
use Siruis\Messaging\Message;

class CoProtocolP2P extends AbstractP2PCoProtocol
{
    /**
     * @var Pairwise
     */
    protected $pairwise;
    /**
     * @var array
     */
    public $protocols;
    /**
     * @var string|null
     */
    protected $thread_id;

    public function __construct(Pairwise $pairwise, array $protocols, int $time_to_live = null)
    {
        parent::__construct($time_to_live);
        $this->pairwise = $pairwise;
        $this->protocols = $protocols;
        $this->thread_id = null;
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     */
    public function send(Message $message): void
    {
        $transport = $this->get_transport_lazy();
        $this->setup($message, false);
        $transport->send($message);
    }

    /**
     * @inheritDoc
     */
    public function get_one()
    {
        $transport = $this->get_transport_lazy();
        return $transport->get_one();
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function switch(Message $message): array
    {
        $transport = $this->get_transport_lazy();
        $this->setup($message);
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

    public function setup(Message $message, bool $please_ack = true): void
    {
        if ($please_ack && !array_key_exists(CoProtocols::PLEASE_ACK_DECORATOR, $message->payload)) {
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
     * @return mixed
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_transport_lazy()
    {
        if (!$this->transport) {
            $this->hub = Hub::current_hub();
            $agent = $this->hub->get_agent_connection_lazy();
            $this->transport = $agent->spawnPairwise($this->pairwise);
            $this->transport->start($this->protocols, $this->time_to_live);
            $this->is_start = true;
        }
        return $this->transport;
    }
}