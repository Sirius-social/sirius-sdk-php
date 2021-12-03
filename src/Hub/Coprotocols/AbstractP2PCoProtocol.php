<?php


namespace Siruis\Hub\Coprotocols;


use Siruis\Messaging\Message;

abstract class AbstractP2PCoProtocol extends AbstractCoProtocol
{
    abstract public function send(Message $message);

    /**
     * Accumulate event from participant
     *
     * @return ?Message|?string message, sender_verkey, recipient_verkey
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     */
    abstract public function get_one();

    abstract public function switch(Message $message): array;
}