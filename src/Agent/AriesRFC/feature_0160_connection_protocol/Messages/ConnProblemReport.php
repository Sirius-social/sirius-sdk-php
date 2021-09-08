<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages;


use Siruis\Agent\Base\AriesProblemReport;
use Siruis\Messaging\Message;

class ConnProblemReport extends AriesProblemReport
{
    public $PROTOCOL = 'connections';

    public function __construct(array $payload, ...$args)
    {
        parent::__construct($payload, ...$args);
        Message::registerMessageClass(ConnProblemReport::class, $this->PROTOCOL, $this->NAME);
    }
}