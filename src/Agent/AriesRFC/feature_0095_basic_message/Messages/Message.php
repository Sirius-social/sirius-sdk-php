<?php


namespace Siruis\Agent\AriesRFC\feature_0095_basic_message\Messages;


use Siruis\Agent\AriesRFC\Mixins\AttachesMixin;
use Siruis\Agent\AriesRFC\Mixins\PleaseAckMixin;
use Siruis\Agent\AriesRFC\Mixins\ThreadMixin;
use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Helpers\ArrayHelper;

class Message extends AriesProtocolMessage
{
    use ThreadMixin, PleaseAckMixin, AttachesMixin;

    public $PROTOCOL = 'basicmessage';
    public $NAME = 'message';

    public function __construct(array $payload, ?string $content = null, ?string $locale = null, ...$args)
    {
        parent::__construct($payload, ...$args);
        if (!is_null($locale)) {
            $this->payload['~l10n'] = ['locale' => $locale];
        }
        if (!is_null($content)) {
            $this->payload['content'] = $content;
        }
    }

    public function getLocale()
    {
        $l10n = ArrayHelper::getValueWithKeyFromArray('~l10n', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('locale', $l10n);
    }

    public function getContent()
    {
        return ArrayHelper::getValueWithKeyFromArray('content', $this->payload);
    }

    public function getSentTime()
    {
        return ArrayHelper::getValueWithKeyFromArray('sent_time', $this->payload);
    }

    public function setTime()
    {
        $this->payload['sent_time'] = date('Y-m-d H:i:s.up', time());
    }
}