<?php


namespace Siruis\Agent\AriesRFC\feature_0113_question_answer\Messages;


use Siruis\Agent\AriesRFC\Mixins\ThreadMixin;
use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Helpers\ArrayHelper;

/**
 * Implementation of Answer
 * @see https://github.com/hyperledger/aries-rfcs/tree/master/features/0113-question-answer
 */
class Answer extends AriesProtocolMessage
{
    use ThreadMixin;

    public $PROTOCOL = 'questionanswer';
    public $NAME = 'answer';

    public function __construct(
        array $payload, ?string $response = null, ?string $thread_id = null,
        ?string $out_time = null, ...$args
    )
    {
        parent::__construct($payload, ...$args);
        if (!is_null($response)) {
            $this->payload['response'] = $response;
        }
        if (!is_null($thread_id)) {
            $this->setThreadId($thread_id);
        }
        if (!is_null($out_time)) {
            $timing = ArrayHelper::getValueWithKeyFromArray('~timing', $this->payload, []);
            $timing['out_time'] = $out_time;
            $this->payload['~timing'] = $timing;
        }
    }

    public function getResponse()
    {
        return ArrayHelper::getValueWithKeyFromArray('response', $this->payload);
    }

    public function getOutTime()
    {
        $timing = ArrayHelper::getValueWithKeyFromArray('~timing', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('out_time', $timing);
    }

    public function setOutTime()
    {
        $timing = ArrayHelper::getValueWithKeyFromArray('~timing', $this->payload, []);
        $timing['out_time'] = date('Y-m-d H:i:s.up', time());
        $this->payload['~timing'] = $timing;
    }

    public function sign(AbstractCrypto $crypto, Question $question, string $verkey)
    {
        $q_text = $question->getQuestionText() ?? '';
        $response = $this->getResponse() ?? '';
        $nonce = $question->getNonce() ?? '';
        $data = $q_text . $response . $nonce;
        $hasher = hash_init('sha256');
        hash_update($hasher, utf8_encode($data));
        $this->payload['response~sig'] = Utils::sign($crypto, $hasher, $verkey);
    }
}