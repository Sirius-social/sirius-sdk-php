<?php


namespace Siruis\Agent\AriesRFC\Mixins;


use Siruis\Helpers\ArrayHelper;

trait PleaseAckMixin
{
    public function getAckMessageId()
    {
        $please_ack = ArrayHelper::getValueWithKeyFromArray('~please_ack', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('message_id', $please_ack, $this->id);
    }

    public function getPleaseAck()
    {
        return (bool)ArrayHelper::getValueWithKeyFromArray('~please_ack', $this->payload);
    }

    public function setPleaseAck(bool $flag)
    {
        if ($flag) {
            $this->payload['~please_ack'] = ['message_id' => $this->id];
        } elseif (key_exists('~please_ack', $this->payload)) {
            unset($this->payload['~please_ack']);
        }
    }
}