<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Agent\Listener\Event;
use Siruis\Helpers\ArrayHelper;

class CoProtocols
{
    public const PLEASE_ACK_DECORATOR = '~please_ack';
    public const THREAD_DECORATOR = '~thread';

    public static function open_communication(Event $event, int $time_to_live = null): ?AbstractP2PCoProtocol
    {
        if ($event->pairwise && $event->getMessage()) {
            $thread_id = null;
            $parent_thread_id = null;
            $messagePayload = $event->getMessage()->payload;
            if (array_key_exists(self::THREAD_DECORATOR, $messagePayload)) {
                $thread_id = ArrayHelper::getValueWithKeyFromArray('thid', $messagePayload[self::THREAD_DECORATOR]);
            }
            if (array_key_exists(self::PLEASE_ACK_DECORATOR, $messagePayload)) {
                $parent_thread_id = $thread_id;
                $thread_id = ArrayHelper::getValueWithKeyFromArray('message_id', $messagePayload, $event->getMessage()->id);
            }
            if ($thread_id) {
                $comm = new CoProtocolThreadedP2P(
                    $thread_id, $event->pairwise, $parent_thread_id, $time_to_live
                );
            } else {
                $comm = new CoProtocolP2P(
                    $event->pairwise,
                    [$event->getMessage()->getProtocol()],
                    $time_to_live
                );
            }
            return $comm;
        }

        return null;
    }
}