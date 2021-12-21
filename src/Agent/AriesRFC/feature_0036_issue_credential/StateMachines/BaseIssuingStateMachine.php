<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\StateMachines;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\BaseIssueCredentialMessage;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\IssueProblemReport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Base\AbstractStateMachine;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Errors\Exceptions\StateMachineAborted;
use Siruis\Errors\Exceptions\StateMachineTerminatedWithError;
use Siruis\Hub\Coprotocols\CoProtocolP2P;

class BaseIssuingStateMachine extends AbstractStateMachine
{
    public const PROPOSE_NOT_ACCEPTED = 'propose_not_accepted';
    public const OFFER_PROCESSING_ERROR = 'offer_processing_error';
    public const REQUEST_NOT_ACCEPTED = 'request_not_accepted';
    public const ISSUE_PROCESSING_ERROR = 'issue_processing_error';
    public const RESPONSE_FOR_UNKNOWN_REQUEST = 'response_for_unknown_request';

    /**
     * @var null
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

    /**
     * @throws \Siruis\Errors\Exceptions\StateMachineAborted
     */
    public function coprotocol(Pairwise $pairwise): ?CoProtocolP2P
    {
        $this->coprotocol = new CoProtocolP2P(
            $pairwise, [BaseIssueCredentialMessage::PROTOCOL, Ack::PROTOCOL], $this->time_to_live
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
            $this->coprotocol->clean();
            $this->_unregister_for_aborting($this->coprotocol);
        }
    }

    /**
     * @param BaseIssueCredentialMessage $request
     * @param array|null $response_classes
     * @return BaseIssueCredentialMessage|Ack
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\StateMachineTerminatedWithError
     */
    public function switch(BaseIssueCredentialMessage $request, array $response_classes = null)
    {
        while (true) {
            [$ok, $resp] = $this->coprotocol->switch($request);
            if ($ok) {
                if ($resp instanceof BaseIssueCredentialMessage || $resp instanceof Ack) {
                    try {
                        $resp->validate();
                    } catch (SiriusValidationError $error) {
                        $status = $this->_is_leader() ? self::ISSUE_PROCESSING_ERROR : self::REQUEST_NOT_ACCEPTED;
                        throw new StateMachineTerminatedWithError($status, $error->getMessage());
                    }
                    if ($response_classes) {
                        if ($this->__instances($resp, $response_classes)) {
                            return $resp;
                        } else {
                            error_log('Unexpected @type: '.(string)$resp->getType().PHP_EOL.json_encode($resp->payload));
                        }
                    } else {
                        return $resp;
                    }
                } elseif ($resp instanceof IssueProblemReport) {
                    throw new StateMachineTerminatedWithError($resp->problemCode, $resp->explain, false);
                } else {
                    $problem_code = $this->_is_leader() ? self::ISSUE_PROCESSING_ERROR : self::REQUEST_NOT_ACCEPTED;
                    throw new StateMachineTerminatedWithError($problem_code, 'Unexpected response @type: '.(string)$resp->getType());
                }
            } else {
                $problem_code = $this->_is_leader() ? self::ISSUE_PROCESSING_ERROR : self::REQUEST_NOT_ACCEPTED;
                throw new StateMachineTerminatedWithError($problem_code, 'Response awaiting terminated by timeout');
            }
        }
    }

    /**
     * @param BaseIssueCredentialMessage|Ack|IssueProblemReport $msg
     */
    public function send($msg)
    {
        $this->coprotocol->send($msg);
    }

    public function _is_leader(): bool
    {
        return false;
    }
}