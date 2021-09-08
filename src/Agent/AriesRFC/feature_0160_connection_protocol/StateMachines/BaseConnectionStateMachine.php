<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol\StateMachines;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0048_trust_ping\Ping;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnProtocolMessage;
use Siruis\Agent\Connections\Endpoint;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\TheirEndpoint;
use Siruis\Base\AbstractStateMachine;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\StateMachineAborted;
use Siruis\Hub\Coprotocols\AbstractP2PCoProtocol;
use Siruis\Hub\Coprotocols\CoProtocolP2PAnon;

class BaseConnectionStateMachine extends AbstractStateMachine
{
    public const REQUEST_NOT_ACCEPTED = 'request_not_accepted';
    public const REQUEST_PROCESSING_ERROR = 'request_processing_error';
    public const RESPONSE_NOT_ACCEPTED = 'response_not_accepted';
    public const RESPONSE_PROCESSING_ERROR = 'response_processing_error';

    public $problem_report;
    public $time_to_live;
    /**
     * @var Me
     */
    public $me;
    /**
     * @var Endpoint
     */
    public $my_endpoint;
    /**
     * @var AbstractP2PCoProtocol
     */
    public $coprotocol;

    public function __construct(Me $me,
                                Endpoint $my_endpoint,
                                AbstractP2PCoProtocol $coprotocol = null,
                                int $time_to_live = 60,
                                $logger = null)
    {
        parent::__construct($time_to_live, $logger);
        $this->problem_report = null;
        $this->time_to_live = $time_to_live;
        $this->me = $me;
        $this->my_endpoint = $my_endpoint;
        $this->coprotocol = $coprotocol;
    }

    /**
     * @param TheirEndpoint $endpoint
     * @return AbstractP2PCoProtocol|CoProtocolP2PAnon
     * @throws StateMachineAborted
     */
    public function coprotocol(TheirEndpoint $endpoint)
    {
        $co = $this->coprotocol ?? new CoProtocolP2PAnon(
            $this->me->verkey,
            $endpoint,
            [ConnProtocolMessage::PROTOCOL, Ack::PROTOCOL, Ping::$PROTOCOL]
            );
        $this->_register_for_aborting($co);
        try {
            try {
                return $co;
            } catch (OperationAbortedManually $err) {
                $this->log(['progress' => 100, 'message' => 'Aborted']);
                throw new StateMachineAborted('Aborted by User');
            }
        } finally {
            $this->_unregister_for_aborting($co);
        }
    }
}