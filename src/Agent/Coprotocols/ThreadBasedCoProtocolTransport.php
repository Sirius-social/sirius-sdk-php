<?php

namespace Siruis\Agent\Coprotocols;

use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Messaging\Fields\DIDField;
use Siruis\Messaging\Message;

/**
 * CoProtocol based on ~thread decorator
 *
 * See details:
 *  - https://github.com/hyperledger/aries-rfcs/tree/master/concepts/0008-message-id-and-threading
 */
class ThreadBasedCoProtocolTransport extends AbstractCoProtocolTransport
{
    /**
     * @var string
     */
    private $thid;
    /**
     * @var \Siruis\Agent\Pairwise\Pairwise|null
     */
    protected $pairwise;
    /**
     * @var string|null
     */
    private $pthid;
    private $sender_order;
    private $received_orders;
    private $their;

    /**
     * @param string $thid
     * @param \Siruis\Agent\Pairwise\Pairwise|null $pairwise
     * @param \Siruis\Agent\Connections\AgentRPC $rpc
     * @param string|null $pthid
     */
    public function __construct(string $thid, ?Pairwise $pairwise, AgentRPC $rpc, string $pthid = null)
    {
        parent::__construct($rpc);
        $this->thid = $thid;
        $this->pairwise = $pairwise;
        $this->pthid = $pthid;
        $this->sender_order = 0;
        $this->received_orders = [];
    }

    public function getPairwise(): Pairwise
    {
        return $this->pairwise;
    }

    public function setPairwise(?Pairwise $value)
    {
        $this->pairwise = $value;
        if ($value) {
            $this->their = $value->their;
            $this->_setup(
                $value->their->verkey,
                $value->their->endpoint,
                $value->me->verkey,
                $value->their->routing_keys
            );
        } else {
            $this->their = null;
            $this->_setup('', '');
        }
    }

    /**
     * @param array|null $protocols
     * @param int|null $time_to_live
     *
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function start(array $protocols = null, int $time_to_live = null)
    {
        if (is_null($protocols)) {
            $this->check_protocols = false;
        }
        parent::start($protocols, $time_to_live);
        $this->rpc->start_protocol_with_threading($this->thid, $time_to_live);
    }

    public function stop()
    {
        parent::stop();
        $this->rpc->stop_protocol_with_threading($this->thid, true);
    }

    /**
     * @param \Siruis\Messaging\Message $message
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessage
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     */
    public function switch(Message $message): array
    {
        $message = $this->prepare_message($message) ?? $message;
        [$ok, $response] = parent::switch($message);
        if ($ok) {
            $thread = $response->payload[self::THREAD_DECORATOR] ?? [];
            $respond_sender_order = $thread['sender_order'] ?? null;
            if (!is_null($respond_sender_order) && !is_null($this->their)) {
                $recipient = $this->their->did;
                $err = (new DIDField())->validate($recipient);
                if (is_null($err)) {
                    $order = $this->received_orders[$recipient] ?? 0;
                    $this->received_orders[$recipient] = max($order, $respond_sender_order);
                }
            }
        }
        return [$ok, $response];
    }

    /**
     * @param \Siruis\Messaging\Message $message
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function send(Message $message)
    {
        $message = $this->prepare_message($message) ?? $message;
        parent::send($message);
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @param array $to
     *
     * @return array
     *
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     */
    public function send_many(Message $message, array $to): array
    {
        $message = $this->prepare_message($message) ?? $message;
        return parent::send_many($message, $to);
    }

    private function prepare_message(Message $message)
    {
        if (!in_array(self::THREAD_DECORATOR, $message->payload)) {
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
            $this->sender_order += 1;
            $message->payload[self::THREAD_DECORATOR] = $thread_decorator;

            return $message;
        }

        return false;
    }
}