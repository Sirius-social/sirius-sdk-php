<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\Microledgers\Transaction;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;

class BaseTransactionsMessage extends SimpleConsensusMessage
{
    public $NAME = 'stage';
    public $transactions;
    public $state;
    public $hash;
    public $thread_id;

    /**
     * BaseTransactionsMessage constructor.
     * @param array $payload
     * @param array|null $transactions
     * @param MicroLedgerState|null $state
     * @param array|null $participants
     * @param string|null $id_
     * @param string|null $version
     * @param string|null $doc_uri
     * @throws SiriusContextError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     */
    public function __construct(array $payload,
                                array $transactions = null,
                                $state = null,
                                array $participants = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $participants, $id_, $version, $doc_uri);
        $this->transactions = $transactions;
        if ($state) {
            $state = new MicroLedgerState((array)$state);
            $this->state = $state;
            $this->hash = $state->hash;
        }
        $this->transactions = $this->getTransactions();
        $this->state = $this->getState();
        $this->thread_id = $this->getThreadId();
    }

    public function getThreadId()
    {
        $thread = ArrayHelper::getValueWithKeyFromArray(self::THREAD_DECORATOR, $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('thid', $thread);
    }

    /**
     * @return array|null
     * @throws SiriusContextError
     */
    public function getTransactions(): ?array
    {
        $txns = $this->transactions;
        if ($txns) {
            $transactions = [];
            foreach ($txns as $txn) {
                $txn = new Transaction($txn);
                if (!$txn->has_metadata()) {
                    throw new SiriusContextError('Transaction must have processed by Ledger engine and has metadata');
                }
                array_push($transactions, $txn);
            }
            return $transactions;
        } else {
            return null;
        }
    }

    public function getState(): ?MicroLedgerState
    {
        $state = $this->state ? $this->state : null;
        if ($state) {
            $state = new MicroLedgerState((array)$state);
            return $state->is_filled() ? $state : null;
        } else {
            return null;
        }
    }
}