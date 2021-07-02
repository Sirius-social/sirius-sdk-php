<?php


namespace Siruis\Agent\Listener;

use Siruis\Agent\Connections\AgentEvents;
use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusInvalidType;
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

    public function __construct(AgentEvents $source, AbstractPairwiseList $pairwise_resolver = null)
    {
        $this->source = $source;
        $this->pairwise_resolver = $pairwise_resolver;
    }

    /**
     * @param int|null $timeout
     * @return Event
     * @throws SiriusConnectionClosed
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusInvalidType
     */
    public function get_one(int $timeout = null): Event
    {
        $eventMessage = $this->source->pull($timeout);
        $event = json_decode($eventMessage->serialize(), true);
        if (key_exists('message', $event)) {
            list($ok, $message) = Message::restoreMessageInstance($event['message']);
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
