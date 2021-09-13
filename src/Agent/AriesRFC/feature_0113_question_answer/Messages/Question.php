<?php


namespace Siruis\Agent\AriesRFC\feature_0113_question_answer\Messages;


use Siruis\Agent\AriesRFC\Mixins\ThreadMixin;
use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Helpers\ArrayHelper;

/**
 * Implementation of Question
 * @see https://github.com/hyperledger/aries-rfcs/tree/master/features/0113-question-answer
 */
class Question extends AriesProtocolMessage
{
    use ThreadMixin;

    public $PROTOCOL = 'questionanswer';
    public $NAME = 'question';

    public function __construct(
        array $payload, array $valid_responses = null, ?string $question_text = null,
        ?string $question_detail = null, ?string $nonce = null, ?bool $signature_required = null,
        ?string $locale = null, ?string $expires_time = null, ...$args
    )
    {
        parent::__construct($payload, ...$args);
        if (!is_null($locale)) {
            $this->payload['~l10n'] = ['locale' => $locale];
        }
        if (!is_null($valid_responses)) {
            $vs = [];
            foreach ($valid_responses as $s) {
                array_push($vs, ['text' => $s]);
            }
            $this->payload['valid_responses'] = $vs;
        }
        if (!is_null($question_text)) {
            $this->payload['question_text'] = $question_text;
        }
        if (!is_null($question_detail)) {
            $this->payload['question_detail'] = $question_detail;
        }
        if (!is_null($nonce)) {
            $this->payload['nonce'] = $nonce;
        }
        if (!is_null($signature_required)) {
            $this->payload['signature_required'] = $signature_required;
        }
        if ($expires_time) {
            $timing = ArrayHelper::getValueWithKeyFromArray('~timing', $this->payload, []);
            $timing['expires_time'] = $expires_time;
            $this->payload['~timing'] = $timing;
        }
    }

    public function getLocale()
    {
        $l10n = ArrayHelper::getValueWithKeyFromArray('~l10n', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('locale', $l10n);
    }

    public function getValidResponses()
    {
        $valid_responses = ArrayHelper::getValueWithKeyFromArray('valid_responses', $this->payload, []);
        return array_values($valid_responses);
    }

    public function getQuestionText()
    {
        return ArrayHelper::getValueWithKeyFromArray('question_text', $this->payload);
    }

    public function getQuestionDetail()
    {
        return ArrayHelper::getValueWithKeyFromArray('question_detail', $this->payload);
    }

    public function getNonce()
    {
        return ArrayHelper::getValueWithKeyFromArray('nonce', $this->payload);
    }

    public function getSignatureRequired()
    {
        return ArrayHelper::getValueWithKeyFromArray('signature_required', $this->payload);
    }

    public function getExpiresTime()
    {
        $timing = ArrayHelper::getValueWithKeyFromArray('~timing', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('expires_time', $timing);
    }

    public function setTtl(int $seconds)
    {
        $expire_at = date("Y-m-d H:i:s.up", time() + $seconds);
        $timing = ArrayHelper::getValueWithKeyFromArray('~timing', $this->payload, []);
        $timing['expires_time'] = $expire_at;
        $this->payload['~timing'] = $timing;
    }
}