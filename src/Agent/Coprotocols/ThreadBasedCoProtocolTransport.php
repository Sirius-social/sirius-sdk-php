<?php


namespace Siruis\Agent\Coprotocols;


use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
use Siruis\Messaging\Fields\DIDField;
use Siruis\Messaging\Message;

class ThreadBasedCoProtocolTransport extends AbstractCoProtocolTransport
{
    /**
     * @var string
     */
    public $thid;
    /**
     * @var Pairwise|null
     */
    public $pairwise;
    /**
     * @var string|null
     */
    public $pthid;
    /**
     * @var int
     */
    public $sender_order;
    /**
     * @var array
     */
    public $received_orders;
    /**
     * @var Their|null
     */
    public $their;

    /**
     * ThreadBasedCoProtocolTransport constructor.
     * @param string $thid
     * @param \Siruis\Agent\Pairwise\Pairwise|null $pairwise
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     * @param string|null $pthid
     */
    public function __construct(string $thid, ?Pairwise $pairwise, AgentRPC $rpc, string $pthid = null)
    {
        parent::__construct($rpc);
        $this->thid = $thid;
        $this->setPairwise($pairwise);
        $this->pthid = $pthid;
        $this->sender_order = 0;
        $this->received_orders = [];
    }

    public function setPairwise(?Pairwise $value): void
    {
        $this->pairwise = $value;
        if ($value) {
            $this->their = $value->their;
            $this->setup(
                $value->their->verkey,
                $value->their->endpoint,
                $value->me->verkey,
                $value->their->routing_keys
            );
        } else {
            $this->their = null;
            $this->setup('', '');
        }
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
        if (!$protocols) {
            $this->check_protocols = false;
        }
        parent::start($protocols, $time_to_live);
        $this->rpc->start_protocol_with_threading($this->thid, $time_to_live);
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
        $this->rpc->stop_protocol_with_threading($this->thid, true);
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \JsonException
     */
    public function switch(Message $message): array
    {
        $message = $this->prepare_message($message);
        $switchArr = parent::switch($message);
        if ($switchArr[0]) {
            $thread = $switchArr[1]['~thread'] ?: [];
            $respond_sender_order = $thread['sender_order'] ?: null;
            if ($respond_sender_order) {
                $recipient = $this->their->did;
                $didField = new DIDField();
                $err = $didField->validate($recipient);
                if (!$err) {
                    $order = $this->received_orders[$recipient] ?: 0;
                    $this->received_orders[$recipient] = max($order, $respond_sender_order);
                }
            }
        }
        return [$switchArr[0], $switchArr[1]];
    }

    /**
     * @param \Siruis\Messaging\Message &$message
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \JsonException
     */
    public function send(Message $message): void
    {
        $message = $this->prepare_message($message);
        parent::send($message);
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @param array $to
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \JsonException
     */
    public function send_many(Message $message, array $to): array
    {
        $message = $this->prepare_message($message);
        return parent::send_many($message, $to);
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @return \Siruis\Messaging\Message
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    protected function prepare_message(Message $message): Message
    {
        $context = json_decode($message->serialize(), true, 512, JSON_THROW_ON_ERROR);
        if (!array_key_exists(self::THREAD_DECORATOR, $context)) {
            $thread_decorator = [
                'thid' => $this->thid,
                'sender_order' => $this->sender_order
            ];
            if ($this->pthid) {
                $thread_decorator['pthid'] = $this->pthid;
            }
            if ($this->received_orders) {
                $thread_decorator['received_orders'] = $this->received_orders;
            }
            ++$this->sender_order;
            $context[self::THREAD_DECORATOR] = $thread_decorator;
            return new Message($context);
        }
    }
}