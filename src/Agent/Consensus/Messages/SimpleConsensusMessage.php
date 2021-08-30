<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\Base\AriesProtocolMessage;

class SimpleConsensusMessage extends AriesProtocolMessage
{
    public $PROTOCOL = 'simple-consensus';

    public function __construct(array $payload,
                                array $participants = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $id_, $version, $doc_uri);
        $this->payload['participants'] = $participants;
    }

    public function getParticipants()
    {
        return $this->payload['participants'] ?? [];
    }

    public function setParticipants($value)
    {
        $this->payload['participants'] = $value;
    }
}