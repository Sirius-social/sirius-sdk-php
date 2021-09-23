<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;

class CredentialAck extends Ack
{
    public $PROTOCOL = BaseIssueCredentialMessage::PROTOCOL;

    public const PROTOCOL = BaseIssueCredentialMessage::PROTOCOL;
}