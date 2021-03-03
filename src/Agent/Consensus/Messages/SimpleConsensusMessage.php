<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\Base\AriesProtocolMessage;

class SimpleConsensusMessage extends AriesProtocolMessage
{
    const PROTOCOL = 'simple-consensus';
    public $participants;

    public function __construct(array $payload,
                                array $participants = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $id_, $version, $doc_uri);
        $this->participants = $participants;
    }
}