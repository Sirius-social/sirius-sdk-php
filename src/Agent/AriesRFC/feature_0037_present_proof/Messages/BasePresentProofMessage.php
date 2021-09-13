<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages;


use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Helpers\ArrayHelper;

class BasePresentProofMessage extends AriesProtocolMessage
{
    public $PROTOCOL = 'present-proof';
    public $DEF_LOCALE = 'en';

    public const DEF_LOCALE = 'en';
    public const PROTOCOL = 'present-proof';

    public const CREDENTIAL_TRANSLATION_TYPE = 'https://github.com/Sirius-social/agent/tree/master/messages/credential-translation';
    public const CREDENTIAL_TRANSLATION_ID = 'credential-translation';

    public function __construct(array $payload,
                                string $locale = self::DEF_LOCALE,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        $version = $version ?? '1.1';
        parent::__construct($payload, $id_, $version, $doc_uri);
        $this->payload['~l10n'] = ['locale' => $locale];
    }

    public function getLocale()
    {
        $l10n = ArrayHelper::getValueWithKeyFromArray('~l10n', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('locale', $l10n, $this->DEF_LOCALE);
    }

    public function getAckMessageId()
    {
        $please_ack = ArrayHelper::getValueWithKeyFromArray('~please_ack', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('message_id', $please_ack) ?? $this->id;
    }

    public function getPleaseAck()
    {
        $please_ack = ArrayHelper::getValueWithKeyFromArray('~please_ack', $this->payload, []);
        return !is_null($please_ack);
    }

    public function setPleaseAck(bool $flag)
    {
        if ($flag) {
            $this->payload['please_ack'] = ['message_id' => $this->id];
        } elseif (key_exists('~please_ack', $this->payload)) {
            unset($this->payload['~please_ack']);
        }
    }

    public function getThreadId()
    {
        $thread = ArrayHelper::getValueWithKeyFromArray(self::THREAD_DECORATOR, $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('thid', $thread);
    }

    public function setThreadId(string $thid)
    {
        $thread = ArrayHelper::getValueWithKeyFromArray(self::THREAD_DECORATOR, $this->payload, []);
        $thread['thid'] = $thid;
        $this->payload[self::THREAD_DECORATOR] = $thread;
    }
}