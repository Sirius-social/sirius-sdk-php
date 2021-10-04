<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;

class PresentationAck extends Ack
{
    public $PROTOCOL = BasePresentProofMessage::PROTOCOL;

    public const PROTOCOL = BasePresentProofMessage::PROTOCOL;
}