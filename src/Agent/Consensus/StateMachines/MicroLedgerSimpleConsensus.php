<?php


namespace Siruis\Agent\Consensus\StateMachines;


use Exception;
use GuzzleHttp\Exception\GuzzleException;
use http\Exception\RuntimeException;
use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0015_acks\Status;
use Siruis\Agent\Consensus\Locking;
use Siruis\Agent\Consensus\Messages\CommitParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\CommitTransactionsMessage;
use Siruis\Agent\Consensus\Messages\InitRequestLedgerMessage;
use Siruis\Agent\Consensus\Messages\InitResponseLedgerMessage;
use Siruis\Agent\Consensus\Messages\MicroLedgerState;
use Siruis\Agent\Consensus\Messages\PostCommitParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\PostCommitTransactionsMessage;
use Siruis\Agent\Consensus\Messages\PreCommitParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\PreCommitTransactionsMessage;
use Siruis\Agent\Consensus\Messages\ProposeParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\ProposeTransactionsMessage;
use Siruis\Agent\Consensus\Messages\SimpleConsensusProblemReport;
use Siruis\Agent\Microledgers\AbstractBatchedAPI;
use Siruis\Agent\Microledgers\AbstractMicroledger;
use Siruis\Agent\Microledgers\Microledger;
use Siruis\Agent\Microledgers\Microledgers;
use Siruis\Agent\Microledgers\Transaction;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Base\AbstractStateMachine;
use Siruis\Errors\Exceptions\OperationAbortedManually;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Errors\Exceptions\StateMachineAborted;
use Siruis\Errors\Exceptions\StateMachineTerminatedWithError;
use Siruis\Helpers\ArrayHelper;
use Siruis\Hub\Coprotocols\CoProtocolThreadedP2P;
use Siruis\Hub\Coprotocols\CoProtocolThreadedTheirs;
use Siruis\Hub\Init;
use SodiumException;

class MicroLedgerSimpleConsensus extends AbstractStateMachine
{
    const REQUEST_NOT_ACCEPTED = "request_not_accepted";
    const REQUEST_PROCESSING_ERROR = 'request_processing_error';
    const RESPONSE_NOT_ACCEPTED = "response_not_accepted";
    const RESPONSE_PROCESSING_ERROR = 'response_processing_error';

    public $me;
    public $problem_report;
    protected $cached_p2p;

    public function __construct(Me $me, int $time_to_live = 60, $logger = null)
    {
        parent::__construct($time_to_live, $logger);
        $this->me = $me;
        $this->problem_report = null;
        $this->cached_p2p = [];
    }

    public function acceptors(array $theirs, string $thread_id)
    {
        $co = new CoProtocolThreadedTheirs(
            $thread_id, $theirs
        );
        $this->_register_for_aborting($co);
        try {
            try {
                return $co;
            } catch (OperationAbortedManually $exception) {
                $this->log(['progress' => 100, 'message' => 'Aborted']);
                throw new StateMachineAborted('Aborted by User');
            }
        } finally {
            $this->_unregister_for_aborting($co);
        }
    }

    public function leader(Pairwise $their, string $thread_id, int $time_to_live = null): CoProtocolThreadedP2P
    {
        $co = new CoProtocolThreadedP2P(
            $thread_id, $their, $time_to_live ? $time_to_live : $this->time_to_live
        );
        $this->_register_for_aborting($co);
        try {
            try {
                return $co;
            } catch (OperationAbortedManually $exception) {
                $this->log(['progress' => 100, 'message' => 'Aborted']);
                throw new StateMachineAborted('Aborted by User');
            }
        } finally {
            $this->_unregister_for_aborting($co);
        }
    }

    /**
     * @param string $ledger_name
     * @param array $participants
     * @param array $genesis
     * @return array
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SiriusValidationError
     * @throws StateMachineAborted
     */
    public function init_microledger(string $ledger_name, array $participants, array $genesis): array
    {
        $this->_bootstrap($participants);
        $relationships = [];
        foreach ($this->cached_p2p as $p2p) {
            array_push($relationships, $p2p);
        }
        $co = $this->acceptors($relationships, 'simple-consensus-init-' . uniqid());
        $this->log(['progress' => 0, 'message' => "Create ledger [{$ledger_name}]"]);
        $ledgers_create = Init::Microledgers()->create($ledger_name, $genesis);
        $ledger = $ledgers_create[0];
        $genesis = $ledgers_create[1];
        $this->log(['message' => 'Ledger creation terminated successfully']);
        try {
            $this->_init_microledger_internal($co, $ledger, $participants, $genesis);
            $this->log(['progress' => 100, 'message' => 'All participants accepted ledger creation']);
        } catch (Exception $e) {
            Init::Microledgers()->reset($ledger_name);
            $this->log(['message' => 'Reset ledger']);
            if ($e instanceof StateMachineTerminatedWithError) {
                $this->problem_report = new SimpleConsensusProblemReport([], null, null, null, $e->problem_code, $e->explain);
                $this->log(['progress' => 100, 'message' => 'Terminated with error', 'problem_code' => $e->problem_code, 'explain' => $e->explain]);
                if ($e->notify) {
                    $co->send($this->problem_report);
                }
                return [false, null];
            } else {
                $this->log(['progress' => 100, 'message' => 'Terminated with exception', 'exception' => (string)$e]);
                throw new Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }
        return [true, $ledger];
    }

    /**
     * @param Pairwise $leader
     * @param InitRequestLedgerMessage $propose
     * @return array
     * @throws GuzzleException
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusContextError
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SiriusValidationError
     * @throws StateMachineAborted
     * @throws Exception
     */
    public function accept_microledger(Pairwise $leader, InitRequestLedgerMessage $propose): array
    {
        if (!in_array($this->me->did, $propose->participants)) {
            throw new SiriusContextError('Invalid state machine initialization');
        }
        $time_to_live = $propose->timeout_sec ? $propose->timeout_sec : $this->time_to_live;
        $this->_bootstrap($propose->participants);
        $co = $this->leader($leader, $propose->thread_id, $time_to_live);
        $ledger_name = ArrayHelper::getValueWithKeyFromArray('name', $propose->ledger);
        try {
            if (!$ledger_name) {
                throw new StateMachineTerminatedWithError(
                    self::REQUEST_PROCESSING_ERROR,
                    'Ledger name is Empty!'
                );
            }
            foreach ($propose->participants as $their_did) {
                if ($their_did != $this->me->did) {
                    $pw = ArrayHelper::getValueWithKeyFromArray($their_did, $this->cached_p2p);
                    if (!$pw) {
                        throw new StateMachineTerminatedWithError(
                            self::REQUEST_PROCESSING_ERROR,
                            "Pairwise for DID: $their_did does not exists",
                        );
                    }
                }
            }
            $this->log(['progress' => 0, 'message' => "Start ledger [{$ledger_name}] creation process"]);
            $ledger = $this->_accept_microledger_internal($co, $leader, $propose, $time_to_live);
            $this->log(['progress' => 100, 'message' => 'Ledger creation terminated successfully']);
        } catch (Exception $e) {
            Init::Microledgers()->reset($ledger_name);
            $this->log(['message' => 'Reset ledger']);
            if ($e instanceof StateMachineTerminatedWithError) {
                $this->problem_report = new SimpleConsensusProblemReport($e->problem_code, $e->explain);
                $this->log([
                    'progress' => 100, 'message' => 'Terminated with error',
                    'problem_code' => $e->problem_code, 'explain' => $e->explain
                ]);
                if ($e->notify) {
                    $co->send($this->problem_report);
                }
                return [false, null];
            } else {
                $this->log([
                    'progress' => 100, 'message' => 'Terminated with exception',
                    'exception' => (string)$e
                ]);
                throw new Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }
        return [true, $ledger];
    }

    /**
     * @param Microledger $ledger
     * @param array $participants
     * @param array $transactions
     * @return array
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SiriusValidationError
     * @throws StateMachineAborted
     */
    public function commit(Microledger $ledger, array $participants, array $transactions): array
    {
        $this->_bootstrap($participants);
        $relationships = [];
        foreach ($this->cached_p2p as $p2p) {
            array_push($relationships, $p2p);
        }
        $co = $this->acceptors($relationships, 'simple-consensus-commit-' . uniqid());
        try {
            $this->log(['progress' => 0, 'message' => 'Start committing ' . count($transactions) . ' transactions']);
            $txns = $this->_commit_internal($co, $ledger, $transactions, $participants);
            $this->log(['progress' => 100, 'message' => 'Commit operation was accepted by all participants']);
            return [true, $txns];
        } catch (Exception $e) {
            $ledger->reset_uncommitted();
            $this->log(['message' => 'Reset uncommitted']);
            if ($e instanceof StateMachineTerminatedWithError) {
                $this->problem_report = new SimpleConsensusProblemReport($e->problem_code, $e->explain);
                $this->log([
                    'progress' => 100, 'message' => 'Terminated with error',
                    'problem_code' => $e->problem_code, 'explain' => $e->explain
                ]);
                if ($e->notify) {
                    $co->send($this->problem_report);
                }
                return [false, null];
            } else {
                $this->log([
                    'progress' => 100, 'message' => 'Terminated with exception',
                    'exception' => (string)$e
                ]);
                throw new Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }
    }

    public function commit_in_parallel(array $ledgers, array $participants, array $transactions)
    {
        $this->_bootstrap($participants);
        $relationships = [];
        foreach (array_values($this->cached_p2p) as $p2p) {
            array_push($relationships, $p2p);
        }
        $co = $this->acceptors($relationships, 'simple-consensus-commit-parallel-' . uniqid());
        $batching_api = Init::Microledgers()->batched();
        $ledgers_for_commit = [];
        if (!$batching_api) {
            foreach ($ledgers as $ledger) {
                if ($ledger instanceof AbstractMicroledger) {
                    array_push($ledgers_for_commit, $ledger);
                } elseif (is_string($ledger)) {
                    $inst = Init::Microledgers()->ledger($ledger);
                    array_push($ledgers_for_commit, $inst);
                } else {
                    throw new RuntimeException('Unexpected ledger type: ' . gettype($ledger));
                }
            }
        } else {
            $ledgers_for_commit = $batching_api->open($ledgers);
        }
        try {
            try {
                $this->log(['progress' => 0, 'message' => 'Start parallel committing of ' . count($transactions) . ' transactions']);
                $this->_commit_internal_parallel($co, $ledgers_for_commit, $transactions, $participants, $batching_api);
                $this->log(['progress' => 100, 'message' => 'Commit operation was accepted by all participants']);
                return true;
            } catch (Exception $e) {
                if (!$batching_api) {
                    foreach ($ledgers_for_commit as $ledger) {
                        $ledger->reset_uncommitted();
                    }
                } else {
                    $batching_api->reset_uncommitted();
                }
                $this->log(['message' => 'Reset uncommitted']);
                if ($e instanceof StateMachineTerminatedWithError) {
                    $this->problem_report = new SimpleConsensusProblemReport($e->problem_code, $e->explain);
                    $this->log(
                        ['progress' => 100, 'message' => 'Terminated with error',
                        'problem_code' => $e->problem_code, 'explain' => $e->explain]
                    );
                    if ($e->notify) {
                        $co->send($this->problem_report);
                    }
                    return false;
                } else {
                    $this->log(['progress' => 100, 'message' => 'Terminated with exception', 'exception' => (string)$e]);
                    throw;
                }
            }
        } finally {
            if ($batching_api) {
                $batching_api->close();
            }
        }
    }

    /**
     * @param Pairwise $leader
     * @param ProposeTransactionsMessage $propose
     * @return bool
     * @throws GuzzleException
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SiriusValidationError
     * @throws StateMachineAborted
     * @throws Exception
     */
    public function accept_commit(Pairwise $leader, ProposeTransactionsMessage $propose): bool
    {
        $time_to_live = $propose->timeout_sec ? $propose->timeout_sec : $this->time_to_live;
        $co = $this->leader($leader, $propose->thread_id, $time_to_live);
        $ledger = null;
        try {
            $this->log(['progress' => 0, 'message' => 'Start acception ' . count($propose->transactions) . ' transactions']);
            $ledger = $this->_load_ledger($propose);
            $this->_accept_commit_internal($co, $ledger, $leader, $propose);
            $this->log(['progress' => 100, 'message' => 'Acception terminated successfully']);
            return true;
        } catch (Exception $e) {
            if ($ledger) {
                $ledger->reset_uncommitted();
                $this->log(['message' => 'Reset uncommitted']);
            }
            if ($e instanceof StateMachineTerminatedWithError) {
                $this->problem_report = new SimpleConsensusProblemReport($e->problem_code, $e->explain);
                $this->log([
                    'progress' => 100, 'message' => 'Terminated with error',
                    'problem_code' => $e->problem_code, 'explain' => $e->explain
                ]);
                if ($e->notify) {
                    $co->send($this->problem_report);
                }
                return false;
            } else {
                throw new Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }
    }

    public function accept_commit_parallel(Pairwise $leader, ProposeParallelTransactionsMessage $propose)
    {
        $time_to_live = $propose->getTimeoutSec() ?? $this->time_to_live;
        $co = $this->leader($leader, $propose->getThreadId(), $time_to_live);
        $batching_api = Init::Microledgers()->batched();
        /** @var AbstractMicroledger[] $ledgers_for_commit */
        if (!$batching_api) {
            $ledgers_for_commit = $this->_load_ledgers($propose);
        } else {
            $ledgers_for_commit = $batching_api->open($propose->getLedgers());
        }
        try {
            try {
                $this->log(['progress' => 0, 'message' => 'Start accept '.count($propose->getTransactions()).' transactions in parallel mode']);
                $this->_accept_commit_internal_parallel($co, $ledgers_for_commit, $leader, $propose, $batching_api);
                $this->log(['progress' => 100, 'message' => 'Accept terminated successfully in parallel mode']);
                return true;
            } catch (Exception $err) {
                if (!$batching_api) {
                    foreach ($ledgers_for_commit as $ledger) {
                        $ledger->reset_uncommitted();
                    }
                } else {
                    $batching_api->reset_uncommitted();
                }
                $this->log(['message' => 'Reset uncommitted']);
                if ($err instanceof StateMachineTerminatedWithError) {
                    $this->problem_report = new SimpleConsensusProblemReport([], null, null, null, $err->problem_code, $err->explain);
                    $this->log(['progress' => 100, 'message' => 'Terminated with error', 'problem_code' => $err->problem_code, 'explain' => $err->explain]);
                    if ($err->notify) {
                        $co->send($this->problem_report);
                    }
                    return false;
                } else {
                    throw;
                }
            }
        } finally {
            if ($batching_api) {
                $batching_api->close();
            }
        }
    }

    /**
     * @param array $participants
     * @throws SiriusValidationError
     */
    public function _bootstrap(array $participants)
    {
        foreach ($participants as $did) {
            if ($did != $this->me->did) {
                if (!in_array($did, $this->cached_p2p)) {
                    $p = Init::PairwiseList()->load_for_did($did);
                    if (!$p) {
                        throw new SiriusValidationError('Unknown pairwise for DID: ' . $did);
                    }
                    $this->cached_p2p[$did] = $p;
                }
            }
        }
    }

    /**
     * @param ProposeTransactionsMessage $propose
     * @return AbstractMicroledger
     * @throws StateMachineTerminatedWithError
     */
    public function _load_ledger(ProposeTransactionsMessage $propose): AbstractMicroledger
    {
        try {
            $this->_bootstrap($propose->participants);
            $propose->validate();
            if (count($propose->participants) < 2) {
                throw new SiriusValidationError('Stage-1: participant count less than 2');
            }
            if (!in_array($this->me->did, $propose->participants)) {
                throw new SiriusValidationError('Stage-1: ' . $this->me->did . ' is not participant');
            }
            $is_ledger_exists = Init::Microledgers()->is_exists($propose->state->name);
            if (!$is_ledger_exists) {
                throw new SiriusValidationError('Stage-1: Ledger with name ' . $propose->state->name . ' does not exists');
            }
        } catch (SiriusValidationError $exception) {
            throw new StateMachineTerminatedWithError(self::RESPONSE_NOT_ACCEPTED, $exception->getMessage());
        }
        return Init::Microledgers()->ledger($propose->state->name);
    }

    public function _load_ledgers(ProposeParallelTransactionsMessage $propose)
    {
        $ledgers = [];
        try {
            $this->_bootstrap($propose->participants);
            $propose->validate();
            if (count($propose->participants) < 2) {
                throw new SiriusValidationError('Stage-1: participant count less than 2');
            }
            if (!key_exists($this->me->did, $propose->participants)) {
                throw new SiriusValidationError('Stage-1: '.$this->me->did.' is not participant');
            }
            foreach ($propose->getLedgers() as $ledger_name) {
                $is_ledger_exists = Init::Microledgers()->is_exists($ledger_name);
                if ($is_ledger_exists) {
                    $ledger = Init::Microledgers()->ledger($ledger_name);
                    array_push($ledgers, $ledger);
                } else {
                    throw new SiriusValidationError('Stage-1: Ledger with name '.$ledger_name.' does not exists');
                }
            }
        } catch (SiriusValidationError $error) {
            throw new StateMachineTerminatedWithError(
                self::RESPONSE_NOT_ACCEPTED,
                $error->getMessage()
            );
        }
        return $ledgers;
    }

    /**
     * @param AbstractMicroledger[] $ledgers
     * @param Transaction[] $transactions
     * @param string|null $txn_time
     * @param AbstractBatchedAPI|null $batching_api
     * @return MicroLedgerState[]
     */
    public function _append_txns(array $ledgers, array $transactions, string $txn_time = null, $batching_api = null)
    {
        foreach ($transactions as $txn) {
            if ($txn_time) {
                $txn->setTime($txn_time);
            }
            if (!$txn->has_metadata()) {
                throw new \RuntimeException('Transaction must to have metadata');
            }
            if (!$txn->getTime()) {
                $txn->setTime($txn_time);
            }
            $states = [];
            if (!$batching_api) {
                foreach ($ledgers as $ledger) {
                    list($pos1, $pos2, $new_txns) = $ledger->append($transactions);
                    array_push($states, MicroLedgerState::from_ledger($ledger));
                }
            } else {
                $_ = $batching_api->append($transactions);
                foreach ($_ as $item) {
                    array_push($states, MicroLedgerState::from_ledger($item));
                }
            }
            return $states;
        }
    }

    /**
     * @param CoProtocolThreadedTheirs $co
     * @param Microledger $ledger
     * @param array $participants
     * @param array $genesis
     * @throws OperationAbortedManually
     * @throws SiriusValidationError
     * @throws StateMachineTerminatedWithError
     * @throws SiriusConnectionClosed
     * @throws SiriusContextError
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SodiumException
     */
    public function _init_microledger_internal(CoProtocolThreadedTheirs $co, Microledger $ledger, array $participants, array $genesis)
    {
        // ============= STAGE 1: PROPOSE =================
        $dids = [];
        foreach ($participants as $did) {
            array_push($dids, $did);
        }
        $propose = new InitRequestLedgerMessage(
            [], $this->time_to_live, $ledger->name, $genesis, $ledger->getRootHash(), $dids
        );
        $propose->add_signature(Init::Crypto(), $this->me);
        $request_commit = new InitResponseLedgerMessage([], $this->time_to_live, $ledger->name, $genesis, $ledger->getRootHash(), $dids);
        $request_commit->assign_from($propose);

        $this->log(['progress' => 20, 'message' => 'Send propose', 'payload' => $propose->payload]);

        // Switch to await transaction acceptors action
        $results = $co->switch($propose);
        $this->log(['progress' => 30, 'message' => 'Received responses from all acceptors']);

        $errored_acceptors_did = [];
        foreach ($results as $result) {
            $pairwise = $results[0];
            $ok = $results[1][0];
            if (!$ok) {
                array_push($errored_acceptors_did, $pairwise->their->did);
            }
        }

        $this->log(['progress' => 40, 'message' => 'Validate responses']);
        foreach ($results as $result) {
            $pairwise = $results[0];
            $response = $results[1][1];
            if ($response instanceof InitResponseLedgerMessage) {
                $response->validate();
                $response->check_signatures(Init::Crypto(), $pairwise->their->did);
                $signature = $response->signature($pairwise->their->did);
                array_push($request_commit->signatures, $signature);
            } elseif ($response instanceof SimpleConsensusProblemReport) {
                throw new StateMachineTerminatedWithError($response->problemCode(), $response->explain());
            }
        }

        // ============= STAGE 2: COMMIT ============
        $this->log(['progress' => 60, 'message' => 'Send commit request', 'payload' => $request_commit->payload]);
        $results = $co->switch($request_commit);
        $this->log(['progress' => 70, 'message' => 'Received commit responses']);
        $errored_acceptors_did = [];
        foreach ($results as $result) {
            $pairwise = $result[0];
            $ok = $result[1][0];
            if (!$ok) {
                array_push($errored_acceptors_did, $pairwise->their->did);
            }
        }
        if ($errored_acceptors_did) {
            throw new StateMachineTerminatedWithError(self::REQUEST_PROCESSING_ERROR, 'Stage-2: Participants ' . $errored_acceptors_did . ' unreachable');
        }
        $this->log(['progress' => 80, 'message' => 'Validate commit responses from acceptors']);
        foreach ($results as $result) {
            $pairwise = $result[0];
            $response = $result[1][1];
            if ($response instanceof SimpleConsensusProblemReport) {
                throw new StateMachineTerminatedWithError(
                    self::RESPONSE_PROCESSING_ERROR,
                    'Participant DID: ' . $pairwise->their->did . ' declined operation with error: ' . $response->explain()
                );
            }
        }

        // ============== STAGE 3: POST-COMMIT ============
        $ack = new Ack(null, null, null, null, null, Status::OK);
        $this->log(['progress' => 90, 'message' => 'All checks OK. Send Ack to acceptors']);
        $co->send($ack);
    }

    /**
     * @param CoProtocolThreadedP2P $co
     * @param Pairwise $leader
     * @param InitRequestLedgerMessage $propose
     * @param int $timeout
     * @return mixed
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusContextError
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     * @throws SodiumException
     * @throws StateMachineTerminatedWithError
     * @throws GuzzleException
     */
    public function _accept_microledger_internal(CoProtocolThreadedP2P $co, Pairwise $leader, InitRequestLedgerMessage $propose, int $timeout)
    {
        // =============== STAGE 1: PROPOSE ===============
        try {
            $propose->validate();
            $propose->check_signatures(Init::Crypto(), $leader->their->did);
            if (count($propose->participants) < 2) {
                throw new SiriusValidationError('Stage-1: participants less than 2');
            }
        } catch (SiriusValidationError $exception) {
            throw new StateMachineTerminatedWithError(
                self::RESPONSE_NOT_ACCEPTED, $exception->getMessage()
            );
        }
        $genesis = [];
        foreach ($propose->ledger['genesis'] as $txn) {
            array_push($genesis, new Transaction($txn->payload));
        }
        $this->log(['progress' => 10, 'message' => 'Initialize ledger']);
        $created = Init::Microledgers()->create($propose->ledger['name'], $genesis);
        $this->log(['progress' => 20, 'message' => 'Ledger initialized successfully']);
        $ledger = $created[0];
        $txns = $created[1];
        if ($propose->ledger['root_hash'] != $ledger->getRootHash()) {
            Init::Microledgers()->reset($ledger->name);
            throw new StateMachineTerminatedWithError(self::RESPONSE_PROCESSING_ERROR, 'Stage-1: Non-consistent Root Hash');
        }
        $response = new InitResponseLedgerMessage($propose->payload, $timeout);
        $response->assign_from($propose);
        $commit_ledger_hash = $response->ledger_hash;
        $response->add_signature(Init::Crypto(), $this->me);
        // =============== STAGE 2: COMMIT ===============
        $this->log(['progress' => 30, 'message' => 'Send propose response', 'payload' => $response->payload]);
        $switched = $co->switch($response);
        $ok = $switched[0];
        $request_commit = $switched[1];
        if ($ok) {
            $this->log(['progress' => 50, 'message' => 'Validate request commit']);
            if ($request_commit instanceof InitResponseLedgerMessage) {
                try {
                    $request_commit->validate();
                    $hashes = $request_commit->check_signatures(Init::Crypto(), 'ALL');
                    foreach ($hashes as $hash) {
                        if ($hash[1] != $commit_ledger_hash) {
                            throw new SiriusValidationError('Stage-2: NonEqual Ledger hash with participant ' . $hash[0]);
                        }
                    }
                } catch (SiriusValidationError $exception) {
                    throw new StateMachineTerminatedWithError(self::REQUEST_NOT_ACCEPTED, $exception->getMessage());
                }
                $commit_participants_set = $request_commit->participants;
                $propose_participants_set = $propose->participants;
                $signers_set = [];
                foreach ($request_commit->signatures as $signature) {
                    array_push($signers_set, $signature['participant']);
                }
                if ($propose_participants_set != $signers_set) {
                    $error_explain = 'Stage-2: Set of signers differs from proposed participants set';
                } elseif ($commit_participants_set != $signers_set) {
                    $error_explain = 'Stage-2: Set of signers differs from commit participants set';
                } else {
                    $error_explain = null;
                }
                if ($error_explain) {
                    throw new StateMachineTerminatedWithError(self::REQUEST_NOT_ACCEPTED, $error_explain);
                } else {
                    // Accept commit
                    $this->log(['progress' => 70, 'message' => 'Send Ack']);
                    $ack = new Ack(null, null, null, null, null, Status::OK);
                    $switched = $co->switch($ack);
                    $ok = $switched[0];
                    $resp = $switched[1];
                    if ($ok) {
                        $this->log(['progress' => 90, 'message' => 'Response to Ack recived']);
                        if ($resp instanceof Ack) {
                            return $ledger;
                        } elseif ($resp instanceof SimpleConsensusProblemReport) {
                            $this->problem_report = $resp;
                            error_log('Code: ' . $resp->problemCode(), '; Explain: ' . $resp->explain());
                            throw new StateMachineTerminatedWithError(
                                $this->problem_report->problemCode(), $this->problem_report->explain()
                            );
                        }
                    } else {
                        throw new StateMachineTerminatedWithError(
                            self::RESPONSE_PROCESSING_ERROR,
                            'Stage-3: Commit accepting was terminated by timeout for actor ' . $leader->their->did
                        );
                    }
                }
            } elseif ($request_commit instanceof SimpleConsensusProblemReport) {
                $this->problem_report = $request_commit;
                throw new StateMachineTerminatedWithError(
                    $this->problem_report->problemCode(), $this->problem_report->explain()
                );
            }
        } else {
            throw new StateMachineTerminatedWithError(
                self::REQUEST_PROCESSING_ERROR,
                'Stage-2: Commit response awaiting was terminated by timeout for actor ' . $leader->their->did
            );
        }
    }

    /**
     * @param CoProtocolThreadedTheirs $co
     * @param Microledger $ledger
     * @param array $transactions
     * @param array $participants
     * @return array
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusContextError
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusPendingOperation
     * @throws SiriusValidationError
     * @throws SodiumException
     * @throws StateMachineTerminatedWithError
     */
    public function _commit_internal(CoProtocolThreadedTheirs $co, Microledger $ledger, array $transactions, array $participants): array
    {
        $txn_time = (string)time();
        $appended = $ledger->append($transactions, $txn_time);
        $start = $appended[0];
        $end = $appended[1];
        $txns = $appended[2];
        $propose = new ProposeTransactionsMessage(
            null, $txns, MicroLedgerState::from_ledger($ledger), $participants,
            null, null, null, $this->time_to_live
        );
        // ==== STAGE-1 Propose transactions to participants ====
        $commit = new CommitTransactionsMessage(null, null, null, $participants);
        $self_pre_commit = new PreCommitTransactionsMessage(null, null, $propose->state);
        $self_pre_commit->sign_state(Init::Crypto(), $this->me);
        $commit->add_pre_commit($this->me->did, $self_pre_commit);

        $this->log(['progress' => 20, 'message' => 'Send Propose to participants', 'payload' => $propose->payload]);
        $results = $co->switch($propose);
        $this->log(['progress' => 30, 'message' => 'Received Propose from participants']);

        $errored_acceptors_did = [];
        foreach ($results as $result) {
            $pairwise = $result[0];
            $ok = $result[1][0];
            if (!$ok) {
                array_push($errored_acceptors_did, $pairwise->their->did);
            }
        }
        if (count($errored_acceptors_did)) {
            throw new StateMachineTerminatedWithError(
                self::REQUEST_PROCESSING_ERROR,
                'Stage-1: Participants ' . json_encode($errored_acceptors_did) . ' unreachable'
            );
        }

        $this->log(['progress' => 50, 'message' => 'Validate responses']);
        foreach ($results as $result) {
            $pairwise = $result[0];
            $pre_commit = $result[1][1];
            if ($pre_commit instanceof PreCommitTransactionsMessage) {
                try {
                    $pre_commit->validate();
                    $verify = $pre_commit->verify_state(Init::Crypto(), $pairwise->their->verkey);
                    $success = $verify[0];
                    $state = $verify[1];
                    if (!$success) {
                        throw new SiriusValidationError('Stage-1: Error verifying signed ledger state for participant ' . $pairwise->their->did);
                    }
                    if ($pre_commit->hash != $propose->state->hash) {
                        throw new SiriusValidationError('Stage-1: Non-consistent ledger state for participant ' . $pairwise->their->did);
                    }
                } catch (SiriusValidationError $exception) {
                    throw new StateMachineTerminatedWithError(
                        self::RESPONSE_NOT_ACCEPTED,
                        'Stage-1: Error for participant ' . $pairwise->their->did . ': ' . $exception->getMessage()
                    );
                } catch (Exception $exception) {
                    $commit->add_pre_commit($pairwise->their->did, $pre_commit);
                }
            } elseif ($pre_commit instanceof SimpleConsensusProblemReport) {
                $this->problem_report = $pre_commit;
                throw new StateMachineTerminatedWithError(
                    $this->problem_report->problemCode(), $this->problem_report->explain()
                );
            }
        }

        // ===== STAGE-2: Accumulate pre-commits and send commit propose to all participants
        $post_commit_all = new PostCommitTransactionsMessage($propose->payload);
        $post_commit_all->add_commit_sign(Init::Crypto(), $commit, $this->me);

        $this->log(['progress' => 60, 'message' => 'Send Commit to participants', 'payload' => $commit->payload]);
        $results = $co->switch($commit);
        $this->log(['progress' => 70, 'message' => 'Received Commit response from participants']);

        $errored_acceptors_did = [];
        foreach ($results as $result) {
            $pairwise = $result[0];
            $ok = $result[1][0];
            if (!$ok) {
                array_push($errored_acceptors_did, $pairwise->their->did);
            }
        }

        if (count($errored_acceptors_did)) {
            throw new StateMachineTerminatedWithError(
                self::REQUEST_PROCESSING_ERROR,
                'Stage-2: Participants ' . json_encode($errored_acceptors_did) . ' unreachable',
            );
        }

        $this->log(['progress' => 80, 'message' => 'Validate responses']);
        foreach ($results as $result) {
            $pairwise = $result[0];
            $post_commit = $result[1][1];
            if ($post_commit instanceof PostCommitTransactionsMessage) {
                try {
                    $post_commit->validate();
                } catch (SiriusValidationError $e) {
                    throw new StateMachineTerminatedWithError(
                        self::RESPONSE_NOT_ACCEPTED,
                        'Stage-2: Error for participant ' . $pairwise->their->did . ': ' . $e->getMessage(),
                    );
                }
            } elseif ($post_commit instanceof SimpleConsensusProblemReport) {
                throw new StateMachineTerminatedWithError(
                    $this->problem_report->problemCode(),
                    'Stage-2: Problem report from participant ' . $pairwise->their->did . ': ' . $post_commit->explain()
                );
            }
        }

        // ===== STAGE-3: Notify all participants with post-commits and finalize process
        $this->log(['progress' => 90, 'message' => 'Send Post-Commit', $post_commit_all->payload]);
        $co->send($post_commit_all);
        $uncommitted_size = $ledger->getUncommittedSize() - $ledger->getSize();
        $ledger->commit($uncommitted_size);
        return $txns;
    }

    /**
     * @param CoProtocolThreadedTheirs $co
     * @param AbstractMicroledger[] $ledgers
     * @param array $transactions
     * @param array $participants
     * @param AbstractBatchedAPI|null $batching_api
     * @throws SiriusContextError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     * @throws StateMachineTerminatedWithError
     */
    public function _commit_internal_parallel(
        CoProtocolThreadedTheirs $co, array $ledgers, array $transactions, array $participants, AbstractBatchedAPI $batching_api = null
    )
    {
        $ledger_names = [];
        foreach ($ledgers as $ledger) {
            array_push($ledger_names, $ledger->getName());
        }
        list($success, $busy) = Locking::acquire($ledger_names, $this->time_to_live);
        if (!$success) {
            throw new StateMachineTerminatedWithError(
                self::REQUEST_NOT_ACCEPTED,
                'Preparing: Ledgers '.implode('.', $busy).' are locked by other state-machine',
                false
            );
        }
        try {
            $txn_time = (string)time();
            $new_transactions = [];
            foreach ($transactions as $txn) {
                array_push($new_transactions, Transaction::create($txn));
            }
            $states = $this->_append_txns($ledgers, $new_transactions, $txn_time, $batching_api);
            $propose = new ProposeParallelTransactionsMessage([],
                $new_transactions,
                $states,
                $participants,
                null,
                null,
                null,
                $this->time_to_live
            );
            // ==== STAGE-1 Propose transactions to participants ====
            $commit = new CommitParallelTransactionsMessage([], $participants);
            $self_pre_commit = new PreCommitParallelTransactionsMessage(
                [], null, null, null, null, $new_transactions, $states
            );
            $self_pre_commit->sign_states(Init::Crypto(), $this->me);
            $commit->add_pre_commit($this->me->did, $self_pre_commit);
            $this->log(['progress' => 20, 'message' => 'Send Propose to participants (parallel mode)', 'payload' => $propose->payload]);
            $results = $co->switch($propose);
            $this->log(['progress' => 30, 'message' => 'Received Propose from participants (parallel mode)']);

            $errored_acceptors_did = [];
            foreach ($results as list($pairwise, list($ok, $_))) {
                if (!$ok) {
                    array_push($errored_acceptors_did, $pairwise->their->did);
                }
            }
            if (count($errored_acceptors_did)) {
                throw new StateMachineTerminatedWithError(
                    self::REQUEST_PROCESSING_ERROR,
                    'Stage-1: Participants '.implode(',', $errored_acceptors_did).' unreachable (parallel mode)'
                );
            }
            $this->log(['progress' => 50, 'message' => 'Validate responses (parallel mode)']);

            foreach ($results as list($pairwise, list($_, $pre_commit))) {
                if ($pre_commit instanceof PreCommitParallelTransactionsMessage) {
                    try {
                        $pre_commit->validate();
                        list($success, $state) = $pre_commit->verify_state(Init::Crypto(), $pairwise->their->verkey);
                        if (!$success) {
                            throw new SiriusValidationError(
                                'Stage-1: Error verifying signed ledger state for participant '.$pairwise->their->did.' (parallel mode)'
                            );
                        }
                        if ($pre_commit->getHash() != $propose->getHash()) {
                            throw new SiriusValidationError('Stage-1: Non-consistent ledger state for participant '.$pairwise->their->did.' (parallel mode)');
                        }
                    } catch (SiriusValidationError $e) {
                        throw new StateMachineTerminatedWithError(
                            self::RESPONSE_NOT_ACCEPTED,
                            'Stage-1: Error: "'.$e->getMessage().'" (parallel mode)'
                        );
                    }
                } elseif ($pre_commit instanceof SimpleConsensusProblemReport) {
                    $explain = 'Stage-1: Problem report from participant '.$pairwise->their->did.' "'.$pre_commit->explain().'" (parallel mode)';
                    throw new StateMachineTerminatedWithError(
                        self::RESPONSE_NOT_ACCEPTED, $explain
                    );
                }
            }
            // ===== STAGE-2: Accumulate pre-commits and send commit propose to all participants
            $post_commit_all = new PostCommitParallelTransactionsMessage([]);
            $post_commit_all->add_commit_sign(Init::Crypto(), $commit, $this->me);

            $this->log(['progress' => 60, 'message' => 'Send Commit to participants (parallel mode)', 'payload' => $commit->payload]);
            $results = $co->switch($commit);
            $this->log(['progress' => 70, 'message' => 'Received Commit response from participants (parallel mode)']);

            $errored_acceptors_did = [];
            foreach ($results as list($pairwise, list($ok, $_))) {
                if (!$ok) {
                    array_push($errored_acceptors_did, $pairwise->their->did);
                }
            }
            if (count($errored_acceptors_did)) {
                throw new StateMachineTerminatedWithError(
                    self::RESPONSE_NOT_ACCEPTED,
                    'Stage-1: Participants '.implode(',', $errored_acceptors_did).' unreachable (parallel mode)'
                );
            }

            $this->log(['progress' => 80, 'message' => 'Validate responses (parallel mode)']);
            foreach ($results as list($pairwise, list($_, $post_commit))) {
                if ($post_commit instanceof PostCommitParallelTransactionsMessage) {
                    try {
                        if (!$post_commit->validate()) {
                            $post_commit_all->extendCommits($post_commit->getCommits());
                        }
                    } catch (SiriusValidationError $error) {
                        throw new StateMachineTerminatedWithError(
                            self::RESPONSE_NOT_ACCEPTED,
                            'Stage-2: Error for participant '.$pairwise->their->did.': "'.$error->getMessage().'" (parallel mode)'
                        );
                    }
                } elseif ($post_commit instanceof SimpleConsensusProblemReport) {
                    throw new StateMachineTerminatedWithError(
                        self::RESPONSE_NOT_ACCEPTED,
                        'Stage-2: Error for participant '.$pairwise->their->did.': "'.$post_commit->explain().'" (parallel mode)'
                    );
                }
            }
            // ===== STAGE-3: Notify all participants with post-commits and finalize process
            $this->log(['progress' => 90, 'message' => 'Send Post-Commit', 'payload' => $post_commit_all->payload]);
            $co->send($post_commit_all);
            if (!$batching_api) {
                foreach ($ledgers as $ledger) {
                    $uncommitted_size = $ledger->getUncommittedSize() - $ledger->getSize();
                    $ledger->commit($uncommitted_size);
                }
            } else {
                $batching_api->commit();
            }
        } finally {
            Locking::release();
        }
    }

    /**
     * @param CoProtocolThreadedP2P $co
     * @param AbstractMicroledger $ledger
     * @param Pairwise $leader
     * @param ProposeTransactionsMessage $propose
     * @throws GuzzleException
     * @throws OperationAbortedManually
     * @throws SiriusConnectionClosed
     * @throws SiriusContextError
     * @throws SiriusInitializationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     * @throws SodiumException
     * @throws StateMachineTerminatedWithError
     */
    public function _accept_commit_internal(
        CoProtocolThreadedP2P $co, AbstractMicroledger $ledger,
        Pairwise $leader, ProposeTransactionsMessage $propose
    )
    {
        $ledger->append($propose->transactions);
        $ledger_state = MicroLedgerState::from_ledger($ledger);
        $pre_commit = new PreCommitTransactionsMessage($propose->payload, null, $ledger_state);
        $pre_commit->sign_state(Init::Crypto(), $this->me);
        $this->log(['progress' => 10, 'message' => 'Send Pre-commit', 'payload' => $pre_commit->payload]);

        $switch = $co->switch($pre_commit);
        $ok = $switch[0];
        $commit = $switch[1];
        if ($ok) {
            $this->log(['progress' => 20, 'message' => 'Received Pre-Commit response', 'payload' => $commit->payload]);
            if ($commit instanceof CommitTransactionsMessage) {
                try {
                    $this->log(['progress' => 30, 'message' => 'Validate Commit']);
                    if ($commit->participants != $propose->participants) {
                        throw new SiriusValidationError('Non-consistent participants');
                    }
                    $commit->validate();
                    $commit->verify_pre_commits(Init::Crypto(), $ledger_state);
                } catch (SiriusValidationError $e) {
                    throw new StateMachineTerminatedWithError(
                        self::REQUEST_NOT_ACCEPTED,
                        'Stage-2: error for actor' . $leader->their->did . ': ' . $e->getMessage(),
                    );
                }
                try {
                    $post_commit = new PostCommitTransactionsMessage([]);
                    $post_commit->add_commit_sign(Init::Crypto(), $commit, $this->me);

                    $this->log(['progress' => 50, 'message' => 'Send Post-Commit', 'payload' => $post_commit->payload]);
                    $switch = $co->switch($post_commit);
                    $ok = $switch[0];
                    $post_commit_all = $switch[1];
                    if ($ok) {
                        $this->log(['progress' => 60, 'message' => 'Received Post-Commit response', 'payload' => $post_commit_all->payload]);
                        if ($post_commit_all instanceof PostCommitTransactionsMessage) {
                            try {
                                $this->log(['progress' => 80, 'message' => 'Validate response']);
                                $post_commit_all->validate();

                                $verkeys = [];
                                foreach ($this->cached_p2p as $p2p) {
                                    array_push($verkeys, $p2p->their->verkey);
                                }
                                $post_commit_all->verify_commits(Init::Crypto(), $commit, $verkeys);
                            } catch (SiriusValidationError $e) {
                                throw new StateMachineTerminatedWithError(
                                    self::REQUEST_NOT_ACCEPTED,
                                    'Stage-3: error for leader ' . $leader->their->did . ': ' . $e->getMessage(),
                                );
                            }
                        }
                    }
                    $uncommitted_size = $ledger_state->uncommitted_size - $ledger_state->size;
                    $this->log(['progress' => 90, 'message' => 'Flush transactions to Ledger storage']);
                    $ledger->commit($uncommitted_size);
                } catch (Exception $e) {
                    throw new StateMachineTerminatedWithError(
                        $this->problem_report->problem_code,
                        'Stage-3: Problem report from leader ' . $leader->their->did . ': ' . $post_commit_all->explain(),
                    );
                }
            } elseif ($commit instanceof SimpleConsensusProblemReport) {
                $explain = 'Stage-1: Problem report from leader ' . $leader->their->did . ': ' . $commit->explain();
                $this->problem_report = new SimpleConsensusProblemReport($commit->problemCode(), $explain);
                throw new StateMachineTerminatedWithError($this->problem_report->problemCode(), $this->problem_report->explain());
            }
        } else {
            throw new StateMachineTerminatedWithError(
                self::REQUEST_PROCESSING_ERROR,
                "Stage-1: Commit awaiting terminated by timeout for leader: {$leader->their->did}"
            );
        }
    }

    public function _accept_commit_internal_parallel(
        CoProtocolThreadedP2P $co, array $ledgers, Pairwise $leader,
        ProposeParallelTransactionsMessage $propose, AbstractBatchedAPI $batching_api = null
    )
    {
        $names = [];
        foreach ($ledgers as $ledger) {
            array_push($names, $ledger->getName());
        }
        list($success, $busy) = Locking::acquire($names, $this->time_to_live);
        if (!$success) {
            throw new StateMachineTerminatedWithError(
                self::REQUEST_NOT_ACCEPTED,
                'Preparing: Ledgers '.implode(',', $busy).' are locked by other state-machine'
            );
        }
        try {
            // ===== STAGE-1: Process Propose, apply transactions and response ledgers states on self-side
            $states = $this->_append_txns($ledgers, $propose->getTransactions(), $batching_api);
            $pre_commit = new PreCommitParallelTransactionsMessage(
                [], null, null, null, null, $propose->getTransactions(), $states
            );
            $pre_commit->sign_states(Init::Crypto(), $this->me);
            $this->log(['progress' => 10, 'message' => 'Send Pre-Commit (parallel mode)', 'payload' => $pre_commit->payload]);

            list($ok, $commit) = $co->switch($pre_commit);
            if ($ok) {
                $this->log(['progress' => 20, 'message' => 'Received Pre-Commit response (parallel mode)', 'payload' => $commit->payload]);
                if ($commit instanceof CommitParallelTransactionsMessage) {
                    // ===== STAGE-2: Process Commit request, check neighbours signatures
                    try {
                        $this->log(['payload' => 30, 'message' => 'Validate Commit (parallel mode)']);
                        if (array_unique($commit->participants) != array_unique($propose->participants)) {
                            throw new SiriusValidationError('Non-consistent participants (parallel mode)');
                        }
                        $commit->validate();
                        $commit->verify_pre_commits(Init::Crypto(), $pre_commit->getHash());
                    } catch (SiriusValidationError $error) {
                        throw new StateMachineTerminatedWithError(
                            self::REQUEST_NOT_ACCEPTED,
                            'Stage-2: error for actor '.$leader->their->did.': "'.$error->getMessage().'" (parallel mode)'
                        );
                    } catch (Exception $err) {
                        // ===== STAGE-3: Process post-commit, verify participants operations
                        $post_commit = new PostCommitParallelTransactionsMessage([]);
                        $post_commit->add_commit_sign(Init::Crypto(), $commit, $this->me);

                        $this->log(['progress' => 50, 'message' => 'Send Post-Commit (parallel mode)', 'payload' => $post_commit->payload]);
                        list($ok, $post_commit_all) = $co->switch($post_commit);
                        if ($ok) {
                            $this->log(['progress' => 60, 'message' => 'Received Post-Commit response (parallel mode)', 'payload' => $post_commit_all->payload]);
                            if ($post_commit_all instanceof PostCommitParallelTransactionsMessage) {
                                try {
                                    $this->log(['payload' => 80, 'message' => 'Validate response (parallel mode)']);
                                    $post_commit_all->validate();

                                    $verkeys = [];
                                    foreach ($this->cached_p2p as $p2p) {
                                        array_push($verkeys, $p2p->their->verkey);
                                    }
                                    $post_commit_all->verify_commits(Init::Crypto(), $commit, $verkeys);
                                } catch (SiriusValidationError $error) {
                                    throw new StateMachineTerminatedWithError(
                                        self::REQUEST_NOT_ACCEPTED,
                                        'Stage-3: error for leader '.$leader->their->did.': "'.$error->getMessage().'" (parallel mode)'
                                    );
                                } catch (Exception $err) {
                                    if (!$batching_api) {
                                        foreach ($ledgers as $ledger) {
                                            $uncommitted_size = $ledger->getUncommittedSize() - $ledger->getSize();
                                            $ledger->commit($uncommitted_size);
                                        }
                                    } else {
                                        $batching_api->commit();
                                    }
                                    $this->log(['progress' => 90, 'message' => 'Flush transactions to Ledger storage']);
                                }
                            } elseif ($post_commit_all instanceof SimpleConsensusProblemReport) {
                                throw new StateMachineTerminatedWithError(
                                    $this->problem_report->problem_code,
                                    'Stage-3: Problem report from leader '.$leader->their->did.': "'.$post_commit_all->explain().'" (parallel mode)'
                                );
                            }
                        } else {
                            throw new StateMachineTerminatedWithError(
                                self::REQUEST_PROCESSING_ERROR,
                                'Stage-3: Post-Commit awaiting terminated by timeout for leader: '.$leader->their->did.' (parallel mode)'
                            );
                        }
                    }
                } elseif ($commit instanceof SimpleConsensusProblemReport) {
                    $explain = 'Stage-1: Problem report from leader '.$leader->their->did.': "'.$commit->explain().'" (parallel mode)';
                    $this->problem_report = new SimpleConsensusProblemReport([], null, null, null, $commit->problemCode, $explain);
                    throw new StateMachineTerminatedWithError($this->problem_report->problemCode, $this->problem_report->explain);
                } else {
                    throw new StateMachineTerminatedWithError(
                        self::REQUEST_NOT_ACCEPTED,
                        'Unexpected message @type: '.gettype($commit->getType()).' (parallel mode)'
                    );
                }
            } else {
                throw new StateMachineTerminatedWithError(
                    self::REQUEST_PROCESSING_ERROR,
                    'Stage-1: Commit awaiting terminated by timeout for leader: '.$leader->their->did.' (parallel mode)'
                );
            }
        } finally {
            Locking::release();
        }
    }
}