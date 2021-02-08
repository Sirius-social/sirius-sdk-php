<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol;


use Siruis\Agent\Connections\Endpoint;
use Siruis\Agent\Pairwise\Me;
use Siruis\Base\AbstractStateMachine;

class BaseConnectionStateMachine extends AbstractStateMachine
{
    public function __construct(Me $me,
                                Endpoint $my_endpoint,
                                int $time_to_live = 60,
                                $logger = null)
    {
        parent::__construct($time_to_live, $logger);
    }
}