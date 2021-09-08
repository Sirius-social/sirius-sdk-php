<?php


namespace Siruis\Agent\AriesRFC\Mixins;


use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Helpers\ArrayHelper;

trait ThreadMixin
{
    public function getThreadId()
    {
        $thread = ArrayHelper::getValueWithKeyFromArray(AriesProtocolMessage::THREAD_DECORATOR, $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('thid', $thread);
    }

    public function setThreadId(string $thid)
    {
        $thread = ArrayHelper::getValueWithKeyFromArray(AriesProtocolMessage::THREAD_DECORATOR, $this->payload, []);
        $thread['thid'] = $thid;
        $this->payload[AriesProtocolMessage::THREAD_DECORATOR] = $thread;
    }
}