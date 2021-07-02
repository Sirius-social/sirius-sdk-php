<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
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
    protected $thread_id;

    public function __construct(Pairwise $pairwise, array $protocols, int $time_to_live = null)
    {
        parent::__construct($time_to_live);
        $this->pairwise = $pairwise;
        $this->protocols = $protocols;
        $this->thread_id = null;
    }

    public function send(Message $message)
    {
        $transport = $this->__get_transport_lazy();
        $this->__setup($message, false);
        $transport->send($message);
    }

    /**
     * @inheritDoc
     */
    public function get_one()
    {
        $transport = $this->__get_transport_lazy();
        return $transport->get_one();
    }

    public function switch(Message $message): array
    {
        $transport = $this->__get_transport_lazy();
        $this->__setup($message);
        $switched = $transport->switch($message);
        $success = $switched[0];
        $response = $switched[1];
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
        if ($please_ack) {
            if (!key_exists(CoProtocols::PLEASE_ACK_DECORATOR, $message->payload)) {
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
            $this->transport = $agent->spawnPairwise($this->pairwise);
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