<?php

use Siruis\Agent\Listener\Event;
use Siruis\Helpers\ArrayHelper;
use Siruis\Hub\Coprotocols\AbstractP2PCoProtocol;
use Siruis\Hub\Coprotocols\CoProtocolP2P;
use Siruis\Hub\Coprotocols\CoProtocolThreadedP2P;

const PLEASE_ACK_DECORATOR = '~please_ack';
const THREAD_DECORATOR = '~thread';

function open_communication(Event $event, int $time_to_live = null): ?AbstractP2PCoProtocol
{
    if ($event->pairwise && $event->getMessage()) {
        $thread_id = null;
        $parent_thread_id = null;
        $messagePayload = $event->getMessage()->payload;
        if (array_key_exists(THREAD_DECORATOR, $messagePayload)) {
            $thread_id = ArrayHelper::getValueWithKeyFromArray('thid', $messagePayload[THREAD_DECORATOR]);
        }
        if (array_key_exists(PLEASE_ACK_DECORATOR, $messagePayload)) {
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
