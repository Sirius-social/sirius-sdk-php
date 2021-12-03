<?php


namespace Siruis\Agent\Listener;

use Siruis\Agent\Connections\AgentEvents;
use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Helpers\ArrayHelper;
use Siruis\Messaging\Message;

class Listener
{
    /**
     * @var AgentEvents
     */
    public $source;
    /**
     * @var AbstractPairwiseList|null
     */
    public $pairwise_resolver;

    /**
     * Listener constructor.
     * @param \Siruis\Agent\Connections\AgentEvents $source
     * @param \Siruis\Agent\Pairwise\AbstractPairwiseList|null $pairwise_resolver
     */
    public function __construct(AgentEvents $source, AbstractPairwiseList $pairwise_resolver = null)
    {
        $this->source = $source;
        $this->pairwise_resolver = $pairwise_resolver;
    }

    /**
     * @param int|null $timeout
     * @return \Siruis\Agent\Listener\Event
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_one(int $timeout = null): Event
    {
        $eventMessage = $this->source->pull($timeout);
        $event = json_decode($eventMessage->serialize(), true, 512, JSON_THROW_ON_ERROR);
        if (array_key_exists('message', $event)) {
            [$ok, $message] = Message::restoreMessageInstance($event['message']);
            if ($ok) {
                $event['message'] = $message;
            } else {
                $event['message'] = new Message($event['message']);
            }
        }
        $their_verkey = ArrayHelper::getValueWithKeyFromArray('sender_verkey', $event);
        if ($this->pairwise_resolver && $their_verkey) {
            $pairwise = $this->pairwise_resolver->load_for_verkey($their_verkey);
        } else {
            $pairwise = null;
        }
        return new Event($event, $pairwise);
    }
}
