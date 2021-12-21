<?php


namespace Siruis\Agent\Coprotocols;

use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Siruis\Agent\Connections\AgentRPC;
use Siruis\Agent\Connections\RoutingBatch;
use Siruis\Agent\Microledgers\MicroledgerList;
use Siruis\Agent\Pairwise\WalletPairwiseList;
use Siruis\Agent\Wallet\DynamicWallet;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;

/**
 * Class AbstractCoProtocolTransport
 * @package Siruis\Agent\Coprotocols
 *
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

    public $rpc;
    public $time_to_live;
    public $check_protocols;
    public $check_verkeys;
    public $default_timeout;
    public $wallet;
    public $microledgers;
    public $pairwise_list;
    public $die_timestamp;
    public $their_vk;
    public $__endpoint;
    public $my_vk;
    public $routing_keys;
    public $is_setup;
    public $protocols;
    public $please_ack_ids;
    public $is_started;

    /**
     * AbstractCoProtocolTransport constructor.
     * @param AgentRPC $rpc
     */
    public function __construct(AgentRPC $rpc)
    {
        $this->time_to_live = null;
        $this->rpc = $rpc;
        $this->check_protocols = true;
        $this->check_verkeys = false;
        $this->default_timeout = $rpc->getTimeout();
        $this->wallet = new DynamicWallet($this->rpc);
        $this->microledgers = new MicroledgerList($this->rpc);
        $this->pairwise_list = new WalletPairwiseList([$this->wallet->pairwise, $this->wallet->did]);
        $this->die_timestamp = null;
        $this->their_vk = null;
        $this->__endpoint = null;
        $this->my_vk = null;
        $this->routing_keys = null;
        $this->is_setup = false;
        $this->protocols = [];
        $this->please_ack_ids = [];
        $this->is_started = false;
    }

    public function setup(string $their_verkey, $endpoint, string $my_verkey = null, array $routing_keys = null): void
    {
        $this->their_vk = $their_verkey;
        $this->my_vk = $my_verkey;
        $this->__endpoint = $endpoint;
        $this->routing_keys = $routing_keys ?: [];
        $this->is_setup = true;
    }

    /**
     * @param array|null $protocols
     * @param int|null $time_to_live
     */
    public function start(array $protocols = null, int $time_to_live = null): void
    {
        $this->protocols = $protocols;
        $this->time_to_live = $time_to_live;
        if ($this->time_to_live) {
            $this->die_timestamp = date("Y-m-d h:i:s", time() + $this->time_to_live);
        } else {
            $this->die_timestamp = null;
        }
        $this->is_started = true;
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function stop(): void
    {
        $this->die_timestamp = null;
        $this->is_started = false;
        $this->cleanup_context();
    }

    /**
     * Send Message to other-side of protocol and wait for response
     *
     * @param Message $message Protocol request
     * @return array [success, Response]
     * @throws Exception|GuzzleException
     */
    public function switch(Message $message): array
    {
        if (!$this->is_setup) {
            throw new SiriusPendingOperation('You must Setup protocol instance at first');
        }
        try {
            $this->rpc->setTimeout($this->get_io_timeout());
            $this->setup_context($message);
            try {
                $event = $this->rpc->sendMessage(
                    $message, $this->their_vk, $this->__endpoint, $this->my_vk,
                    $this->routing_keys, true
                );
            } finally {
                $this->cleanup_context($message);
            }
            if ($this->check_verkeys) {
                $recipient_verkey = $event['recipient_verkey'] ?: null;
                $sender_verkey = $event['sender_verkey'];
                if ($recipient_verkey !== $this->my_vk) {
                    throw new SiriusInvalidPayloadStructure('Unexpected recipient_vekrey: ' . $recipient_verkey);
                }
                if ($sender_verkey !== $this->their_vk) {
                    throw new SiriusInvalidPayloadStructure('Unexpected sender_verkey: ' . $sender_verkey);
                }
                if ($event !== null && array_key_exists('message', $event)) {
                    $message = new Message($event['message']);
                    $payload = json_decode($message->serialize(), true, 512, JSON_THROW_ON_ERROR);
                    $restored = Message::restoreMessageInstance($payload);
                    if (!$restored[0]) {
                        $message = new Message($payload);
                    }
                    if ($this->check_protocols && !in_array(Type::fromString($message->type)->protocol, $this->protocols, true)) {
                        throw new SiriusInvalidMessageClass($message->_type->protocol . ' has unexpected protocol');
                    }
                    return [true, $message];
                }
            } else {
                return [false, null];
            }
        } catch (SiriusTimeoutIO $exception) {
            return [false, null];
        }
    }

    /**
     * @return array
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Exception
     */
    public function get_one(): array
    {
        $timeout = $this->get_io_timeout();
        if ($timeout && $timeout < 0) {
            throw new SiriusTimeoutIO();
        }
        $this->rpc->setTimeout($timeout);
        $message = $this->rpc->read_protocol_message();
        $event = json_decode($message->serialize(), true, 512, JSON_THROW_ON_ERROR);
        if (array_key_exists('message', $event)) {
            $restored = Message::restoreMessageInstance($event['message']);
            if (!$restored[0]) {
                $message = new Message($event['message']);
            }
        } else {
            $message = null;
        }
        $sender_verkey = $event['sender_verkey'] ?: null;
        $recipient_verkey = $event['recipient_verkey'] ?: null;
        return [$message, $sender_verkey, $recipient_verkey];
    }

    /**
     * Send message and don't wait answer
     *
     * @param Message $message
     * @throws SiriusPendingOperation
     * @throws Exception
     * @throws GuzzleException
     */
    public function send(Message $message): void
    {
        if (!$this->is_setup) {
            throw new SiriusPendingOperation('You must Setup protocol instance at first');
        }
        $this->rpc->setTimeout($this->get_io_timeout());
        $this->setup_context($message);
        $this->rpc->sendMessage(
            $message, $this->their_vk, $this->__endpoint, $this->my_vk,
            $this->routing_keys, false, false
        );
    }

    /**
     * @param Message $message
     * @param array $to
     * @return array
     * @throws SiriusPendingOperation
     * @throws SiriusConnectionClosed
     * @throws Exception
     */
    public function send_many(Message $message, array $to): array
    {
        $batches = [];
        foreach ($to as $p) {
            $batches[] = new RoutingBatch(
                $p->their->verkey, $p->their->__endpoint, $p->me->verkey, $p->their->routing_keys
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
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Exception
     */
    public function setup_context(Message $message): void
    {
        $context = json_decode($message->serialize(), true, 512, JSON_THROW_ON_ERROR);
        if (array_key_exists(self::PLEASE_ACK_DECORATOR, $context)) {
            $please_acks = $context[self::PLEASE_ACK_DECORATOR] ?: [];
            $ack_message_id = $please_acks['message_id'] ?: $message->id;
            $ttl = $this->get_io_timeout() ?: 3600;
            $this->rpc->stop_protocol_with_threads(
                [$ack_message_id], $ttl
            );
            $this->please_ack_ids[] = $ack_message_id;
        }
    }


    /**
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function cleanup_context(Message $message = null): void
    {
        if ($message) {
            if (array_key_exists(self::PLEASE_ACK_DECORATOR, $message->payload)) {
                $ack_message_id = $message->payload[self::PLEASE_ACK_DECORATOR]['message_id'] ?: $message['id'];
                $this->rpc->stop_protocol_with_threads([$ack_message_id], true);
                foreach ($this->please_ack_ids as $please_ack_id) {
                    if ($please_ack_id !== $ack_message_id) {
                        $this->please_ack_ids = [$ack_message_id];
                    }
                }
            }
        } else {
            $this->rpc->stop_protocol_with_threads(
                $this->please_ack_ids, true
            );
            $this->please_ack_ids = [];
        }
    }

    /**
     * @return float|int|null
     * @throws Exception
     */
    public function get_io_timeout()
    {
        if ($this->die_timestamp) {
            $now = new DateTime();
            if ($now < $this->die_timestamp) {
                $delta = (new DateTime($this->die_timestamp))->diff($now);
                return $delta->days * self::SEC_PER_DAY + $delta->s;
            }

            return 0;
        }

        return null;
    }


}