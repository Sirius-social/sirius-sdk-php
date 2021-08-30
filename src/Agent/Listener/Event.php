<?php


namespace Siruis\Agent\Listener;


use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Helpers\ArrayHelper;
use Siruis\Messaging\Message;

class Event extends Message
{
    /**
     * @var Pairwise|null
     */
    public $pairwise;
    public $recipient_verkey;
    public $sender_verkey;
    public $forwarded_keys;
    public $content_type;
    public $extra;

    public function __construct(array $payload, Pairwise $pairwise = null)
    {
        parent::__construct($payload);
        $this->pairwise = $pairwise;
        $this->recipient_verkey = $this->getRecipientVerkey();
        $this->sender_verkey = $this->getSenderVerkey();
        $this->forwarded_keys = $this->getForwardedKeys();
        $this->content_type = $this->getContentType();
        $this->extra = $this->getExtra();
    }

    public function getMessage()
    {
        if (key_exists('message', $this->payload)) {
            return $this->payload['message'];
        } else {
            return null;
        }
    }

    public function getRecipientVerkey(): ?string
    {
        return ArrayHelper::getValueWithKeyFromArray('recipient_verkey', $this->payload);
    }

    public function getSenderVerkey(): ?string
    {
        return ArrayHelper::getValueWithKeyFromArray('sender_verkey', $this->payload);
    }

    public function getForwardedKeys(): ?array
    {
        return ArrayHelper::getValueWithKeyFromArray('forwarded_keys', $this->payload);
    }

    public function getContentType(): ?string
    {
        return ArrayHelper::getValueWithKeyFromArray('content_type', $this->payload);
    }

    public function getExtra(): ?array
    {
        return ArrayHelper::getValueWithKeyFromArray('~extra', $this->payload);
    }
}