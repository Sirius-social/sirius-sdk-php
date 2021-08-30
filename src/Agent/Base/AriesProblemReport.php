<?php


namespace Siruis\Agent\Base;


class AriesProblemReport extends AriesProtocolMessage
{
    public $NAME = 'problem_report';
    /**
     * @var string|null
     */
    public $problemCode;
    /**
     * @var string|null
     */
    public $explain;
    /**
     * @var array|null
     */
    public $thread;

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
        if ($problemCode)
            $this->problemCode = $problemCode;
        if ($explain)
            $this->explain = $explain;
        if ($thread_id) {
            $thread = [self::THREAD_DECORATOR];
            $thread['thid'] = $thread_id;
            $payload[self::THREAD_DECORATOR] = $thread;
        }
        parent::__construct($payload, $id_, $version, $doc_uri);
    }

    public function problemCode()
    {
        return $this->problemCode ? $this->problemCode : '';
    }

    public function explain()
    {
        return $this->explain ? $this->explain : '';
    }
}