<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\StateMachines;


use BadMethodCallException;
use Exception;
use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\BasePresentProofMessage;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\PresentProofProblemReport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Base\AbstractStateMachine;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Errors\Exceptions\StateMachineAborted;
use Siruis\Errors\Exceptions\StateMachineTerminatedWithError;
use Siruis\Hub\Coprotocols\CoProtocolP2P;

class BaseVerifyStateMachine extends AbstractStateMachine
{
    public const PROPOSE_NOT_ACCEPTED = "propose_not_accepted";
    public const RESPONSE_NOT_ACCEPTED = "response_not_accepted";
    public const RESPONSE_PROCESSING_ERROR = "response_processing_error";
    public const REQUEST_NOT_ACCEPTED = "request_not_accepted";
    public const RESPONSE_FOR_UNKNOWN_REQUEST = "response_for_unknown_request";
    public const REQUEST_PROCESSING_ERROR = 'request_processing_error';
    public const VERIFY_ERROR = 'verify_error';

    /**
     * @var PresentProofProblemReport|null
     */
    public $problem_report;
    /**
     * @var CoProtocolP2P|null
     */
    public $coprotocol;

    public function __construct(int $time_to_live = 60, $logger = null)
    {
        parent::__construct($time_to_live, $logger);
        $this->problem_report = null;
        $this->time_to_live = $time_to_live;
        $this->coprotocol = null;
    }

    public function getCoprotocol(Pairwise $pairwise)
    {
        $this->coprotocol = new CoProtocolP2P(
            $pairwise,
            [BasePresentProofMessage::PROTOCOL, Ack::PROTOCOL],
            $this->time_to_live
        );
        $this->_register_for_aborting($this->coprotocol);
        try {
            try {
                return $this->coprotocol;
            } catch (OperationAbortedManually $exception) {
                $this->log(['progress' => 100, 'message' => 'Aborted']);
                throw new StateMachineAborted('Aborted by User');
            }
        } finally {
            $this->_unregister_for_aborting($this->coprotocol);
        }
    }

    /**
     * @param BasePresentProofMessage $request
     * @param array $responses_classes
     * @return BasePresentProofMessage|Ack
     * @throws StateMachineTerminatedWithError
     */
    public function switch(BasePresentProofMessage $request, array $responses_classes)
    {
        list($ok, $resp) = $this->coprotocol->switch($request);
        if ($ok) {
            if ($resp instanceof BasePresentProofMessage) {
                try {
                    $resp->validate();
                } catch (SiriusValidationError $error) {
                    $problem_code =
                        $this->_is_leader() ?
                            self::RESPONSE_PROCESSING_ERROR :
                            self::REQUEST_PROCESSING_ERROR;
                    throw new StateMachineTerminatedWithError($problem_code, $error->getMessage());
                }
                if ($responses_classes) {
                    if ($this->is_class_instanceof_resp($resp, $responses_classes)) {
                        return $resp;
                    } else {
                        error_log('Unexpected @type: '. (string)$resp->getType() . ' \n '.json_encode($resp));
                    }
                } else {
                    return $resp;
                }
            } elseif ($resp instanceof PresentProofProblemReport) {
                throw new StateMachineTerminatedWithError($resp->problemCode(), $resp->explain(), false);
            } else {
                $problem_code =
                    $this->_is_leader() ?
                        self::RESPONSE_PROCESSING_ERROR :
                        self::REQUEST_PROCESSING_ERROR;
                throw new StateMachineTerminatedWithError(
                        $problem_code, 'Unexpected response @type: '.(string)$resp->getType()
                );
            }
        }
    }

    /**
     * @param BasePresentProofMessage|Ack|PresentProofProblemReport $msg
     */
    public function send($msg)
    {
        $this->coprotocol->send($msg);
    }

    public function _is_leader()
    {
        throw new BadMethodCallException;
    }

    public function is_class_instanceof_resp($resp, $classes)
    {
        $classes_count = count($classes);
        $instance_classes = [];
        foreach ($classes as $class) {
            if ($class instanceof $resp) {
                array_push($instance_classes, $class);
            }
        }
        return count($classes) == count($instance_classes);
    }
}