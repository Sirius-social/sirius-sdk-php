<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Messaging\Message;

abstract class AbstractP2PCoProtocol extends AbstractCoProtocol
{
    public function __construct(int $time_to_live = null)
    {
        parent::__construct($time_to_live);
    }

    public abstract function send(Message $message);

    /**
     * Accumulate event from participant
     *
     * @return ?Message|?string message, sender_verkey, recipient_verkey
     */
    public abstract function get_one();

    public abstract function switch(Message $message): array;
}