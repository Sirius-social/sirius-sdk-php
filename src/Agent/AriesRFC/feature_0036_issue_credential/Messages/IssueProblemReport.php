<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


use Siruis\Agent\Base\AriesProblemReport;

class IssueProblemReport extends AriesProblemReport
{
    public $PROTOCOL = BaseIssueCredentialMessage::PROTOCOL;

    public const PROTOCOL = BaseIssueCredentialMessage::PROTOCOL;

    public function __construct(
        array $payload,
        string $id_ = null,
        string $version = null,
        string $doc_uri = null,
        string $problemCode = null,
        string $explain = null,
        string $thread_id = null
    )
    {
        parent::__construct($payload, $id_, $version, $doc_uri, $problemCode, $explain, $thread_id);
        self::registerMessageClass(IssueProblemReport::class, $this->PROTOCOL, $this->NAME);
    }
}