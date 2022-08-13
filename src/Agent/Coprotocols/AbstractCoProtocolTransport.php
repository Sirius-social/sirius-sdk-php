<?php

namespace Siruis\Agent\Coprotocols;

use DateTime;
use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Connections\RoutingBatch;
use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Agent\Pairwise\WalletPairwiseList;
use Siruis\Agent\Wallet\DynamicWallet;
use Siruis\Errors\Exceptions\SiriusInvalidMessage;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Helpers\ArrayHelper;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;

/**
 * Abstraction application-level protocols in the context of interactions among agent-like things.
 *
 * Sirius SDK protocol is high-level abstraction over Sirius transport architecture.
 * Approach advantages:
 *  - developer build smart-contract logic in block-style that is easy to maintain and control
 *  - human-friendly source code of state machines in procedural style
 *  - program that is running in separate coroutine: lightweight abstraction to start/kill/state-detection work thread
 * See details:
 *  - https://github.com/hyperledger/aries-rfcs/tree/master/concepts/0003-protocols
 */
abstract class AbstractCoProtocolTransport
{
    public const THREAD_DECORATOR = '~thread';
    public const PLEASE_ACK_DECORATOR = '~please_ack';
    public const SEC_PER_DAY = 86400;
    public const SEC_PER_HOURS = 3600;
    public const SEC_PER_MIN = 60;

    protected $rpc, $check_protocols, $check_verkeys;
    private $time_to_live, $default_timeout, $wallet, $pairwise_list, $die_timestamp, $please_ack_ids;
    private $their_vk, $endpoint, $my_vk, $routing_keys, $is_setup, $protocols, $is_started;

    /**
     * AbstractCoProtocolTransport constructor.
     *
     * @param \Siruis\Agent\Connections\AgentRPC $rpc RPC (independent connection)
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->time_to_live = null;
        $this->rpc = $rpc;
        $this->check_protocols = true;
        $this->check_verkeys = false;
        $this->default_timeout = $rpc->getTimeout();
        $this->wallet = new DynamicWallet($this->rpc);
        $this->pairwise_list = new WalletPairwiseList([$this->wallet->pairwise, $this->wallet->did]);
        $this->die_timestamp = null;
        $this->their_vk = null;
        $this->endpoint = null;
        $this->my_vk = null;
        $this->routing_keys = null;
        $this->is_setup = false;
        $this->protocols = [];
        $this->please_ack_ids = [];
        $this->is_started = false;
    }

    public function getProtocols(): array
    {
        return $this->protocols;
    }

    public function getTimeToLive(): int
    {
        return $this->time_to_live;
    }

    public function getIsStarted(): bool
    {
        return $this->is_started;
    }

    public function getWallet(): DynamicWallet
    {
        return $this->wallet;
    }

    public function getRPC(): AgentRPC
    {
        return $this->rpc;
    }

    public function getPairwiseList(): AbstractPairwiseList
    {
        return $this->pairwise_list;
    }

    public function getIsAlive(): bool
    {
        if ($this->die_timestamp) {
            return idate('Y-m-d h:i:s', time()) < $this->die_timestamp;
        } else {
            return false;
        }
    }

    /**
     * Should be called in Descendant
     *
     * @param string $their_verkey
     * @param string $endpoint
     * @param string|null $my_verkey
     * @param string[]|null $routing_keys
     *
     * @return void
     */
    public function _setup(
        string $their_verkey, string $endpoint, string $my_verkey = null, array $routing_keys = null
    )
    {
        $this->their_vk = $their_verkey;
        $this->my_vk = $my_verkey;
        $this->endpoint = $endpoint;
        $this->routing_keys = $routing_keys ?? [];
        $this->is_setup = true;
    }

    public function start(array $protocols, int $time_to_live = null)
    {
        $this->protocols = $protocols;
        $this->time_to_live = $time_to_live;
        if (is_null($time_to_live)) {
            $this->die_timestamp = null;
        } else {
            $this->die_timestamp = idate('Y-m-d h:i:s', time() + $time_to_live);
        }
        $this->is_started = true;
    }

    /**
     * @return void
     * 
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function stop()
    {
        $this->die_timestamp = null;
        $this->is_started = false;
        $this->cleanup_context();
    }

    /**
     * @param Message $message
     *
     * @return array
     *
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessage
     * @throws \Exception
     */
    public function switch(Message $message): array
    {
        if (!$this->is_setup) {
            throw new SiriusPendingOperation('You must Setup protocol instance at first');
        }

        try {
            $timeout = $this->get_io_timeout();
            if (!is_null($timeout) && $timeout <= 0) {
                throw new SiriusTimeoutIO();
            }
            $this->rpc->setTimeout($timeout);
            $this->setup_context($message);
            try {
                $event = $this->rpc->sendMessage(
                    $message, $this->their_vk, $this->endpoint, $this->my_vk, $this->routing_keys, true
                );
            } finally {
                $this->cleanup_context($message);
            }
            if ($this->check_verkeys) {
                $payload = $event->payload;
                $recipient_verkey = $payload['recipient_verkey'] ?? null;
                $sender_verkey = $payload['sender_verkey'];
                if ($recipient_verkey != $this->my_vk) {
                    throw new SiriusInvalidPayloadStructure("Unexpected recipient_verkey: $recipient_verkey");
                }
                if ($sender_verkey != $this->their_vk) {
                    throw new SiriusInvalidPayloadStructure("Unexpected sender_verkey: $sender_verkey");
                }
            }
            $payload = new Message($event->getAttribute('message') ?? []);
            if (!is_null($payload->payload)) {
                [$ok, $message] = Message::restoreMessageInstance($payload->payload);
                if (!$ok) {
                    $message = new Message($payload->payload);
                }
                if ($this->check_protocols) {
                    if (!in_array(Type::fromString($message->getType())->protocol, $this->protocols)) {
                        throw new SiriusInvalidMessage('@type has unexpected protocol "'. $message->getType()->protocol .'"');
                    }
                }
                return [true, $message];
            } else {
                return [false, null];
            }
        } catch (SiriusTimeoutIO $exception) {
            return [false, null];
        }
    }

    /**
     * @return array
     *
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Exception
     */
    public function get_one(): array
    {
        $timeout = $this->get_io_timeout();
        if (!is_null($timeout) && $timeout <= 0) {
            throw new SiriusTimeoutIO();
        }
        $this->rpc->setTimeout($timeout);
        $event = $this->rpc->read_protocol_message();
        if (in_array('message', $event->payload)) {
            [$ok, $message] = Message::restoreMessageInstance($event->payload);
            if (!$ok) {
                $message = new Message($event->payload['message']);
            }
        } else {
            $message = null;
        }
        $sender_verkey = $event->getAttribute('sender_verkey') ?? null;
        $recipient_verkey = $event->getAttribute('recipient_verkey') ?? null;

        return [$message, $sender_verkey, $recipient_verkey];
    }

    /**
     * Send message and don't wait answer
     *
     * @param \Siruis\Messaging\Message $message
     *
     * @return void
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
        if (!$this->is_setup) {
            throw new SiriusPendingOperation('You must Setup protocol instance at first');
        }

        # $this->rpc->setTimeout($this->get_io_timeout());
        $this->setup_context($message);
        $this->rpc->sendMessage(
            $message, $this->their_vk, $this->endpoint, $this->my_vk, $this->routing_keys, false, true
        );
    }

    /**
     * @param \Siruis\Messaging\Message $message
     * @param \Siruis\Agent\Pairwise\Pairwise[] $to
     *
     * @return array
     *
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Exception
     */
    public function send_many(Message $message, array $to): array
    {
        $batches = [];
        foreach ($to as $p) {
            $batches[] = new RoutingBatch(
                $p->their->verkey, $p->their->endpoint, $p->me->verkey, $p->their->routing_keys
            );
        }
        if (!$this->is_setup) {
            throw new SiriusPendingOperation('You must Setup protocol instance at first');
        }

        $this->rpc->setTimeout($this->get_io_timeout());
        $this->setup_context($message);
        return $this->rpc->send_message_batched($message, $batches);
    }


    /**
     * @param \Siruis\Messaging\Message $message
     *
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Exception
     */
    private function setup_context(Message $message)
    {
        $payload = $message->payload;
        if (in_array(self::PLEASE_ACK_DECORATOR, $payload)) {
            $decorator = ArrayHelper::getValueWithKeyFromArray(self::PLEASE_ACK_DECORATOR, $payload, []);
            $ack_message_id = ArrayHelper::getValueWithKeyFromArray('message_id', $decorator) ?? $message->getId();
            $ttl = $this->get_io_timeout() ?? 3600;
            $this->rpc->start_protocol_with_threads([$ack_message_id], $ttl);
            $this->please_ack_ids[] = $ack_message_id;
        }
    }

    /**
     * @param \Siruis\Messaging\Message|null $message
     *
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    private function cleanup_context(?Message $message = null)
    {
        if (!is_null($message)) {
            $payload = $message->payload;
            if (in_array(self::PLEASE_ACK_DECORATOR, $payload)) {
                $decorator = ArrayHelper::getValueWithKeyFromArray(self::PLEASE_ACK_DECORATOR, $payload, []);
                $ack_message_id = ArrayHelper::getValueWithKeyFromArray('message_id', $decorator) ?? $message->getId();
                $this->rpc->stop_protocol_with_threads([$ack_message_id], true);
                foreach ($this->please_ack_ids as $please_ack_id) {
                    if ($please_ack_id !== $ack_message_id) {
                        $this->please_ack_ids[] = $ack_message_id;
                    }
                }
            }
        } else {
            $this->rpc->stop_protocol_with_threads($this->please_ack_ids, true);
            $this->please_ack_ids = [];
        }
    }

    /**
     * @return float|int|null
     *
     * @throws \Exception
     */
    private function get_io_timeout()
    {
        if ($this->die_timestamp) {
            $now = time();
            if ($now < $this->die_timestamp) {
                $delta = $now - $this->die_timestamp;
                $delta = new DateTime(date('Y-m-d h:i:s', $delta));
                return (int) $delta->format('d') * (self::SEC_PER_DAY + (int) $delta->format('s'));
            } else {
                return 0;
            }
        } else {
            return null;
        }
    }
}