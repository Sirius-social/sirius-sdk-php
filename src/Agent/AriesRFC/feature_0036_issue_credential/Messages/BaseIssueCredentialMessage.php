<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Helpers\ArrayHelper;

class BaseIssueCredentialMessage extends AriesProtocolMessage
{
    public const CREDENTIAL_PREVIEW_TYPE = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/issue-credential/1.0/credential-preview';
    public const CREDENTIAL_TRANSLATION_TYPE = 'https://github.com/Sirius-social/agent/tree/master/messages/credential-translation';
    public const ISSUER_SCHEMA_TYPE = 'https://github.com/Sirius-social/agent/tree/master/messages/issuer-schema';
    public const CREDENTIAL_TRANSLATION_ID = 'credential-translation';
    public const ISSUER_SCHEMA_ID = 'issuer-schema';

    public $PROTOCOL = 'issue-credential';
    public $DEF_LOCALE = 'en';

    public const PROTOCOL = 'issue-credential';
    public const DEF_LOCALE = 'en';

    public function __construct(
        array $payload,
        string $locale = self::DEF_LOCALE,
        string $id_ = null,
        string $version = null,
        string $doc_uri = null
    )
    {
        $version = $version ?? '1.1';
        parent::__construct($payload, $id_, $version, $doc_uri);
        $this->payload['~l10n'] = ['locale' => $locale];
        self::registerMessageClass(BaseIssueCredentialMessage::class, $this->PROTOCOL, $this->NAME);
    }

    public function getLocale()
    {
        $l10n = ArrayHelper::getValueWithKeyFromArray('~l10n', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('locale', $l10n, $this->DEF_LOCALE);
    }

    public function getAckMessageId()
    {
        $please_ack = ArrayHelper::getValueWithKeyFromArray('~please_ack', $this->payload, []);
        $message_id = ArrayHelper::getValueWithKeyFromArray('message_id', $please_ack);
        return $message_id ?? $this->getId();
    }

    public function getPleaseAck()
    {
        return !is_null(ArrayHelper::getValueWithKeyFromArray('~please_ack', $this->payload));
    }

    public function setPleaseAck(bool $flag)
    {
        if ($flag) {
            $this->payload['~please_ack'] = ['message_id' => $this->getId()];
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