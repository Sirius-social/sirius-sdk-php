<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\Microledgers\Transaction;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Helpers\ArrayHelper;

class BaseParallelTransactionsMessage extends SimpleConsensusMessage
{
    public $NAME = 'stage-parallel';

    public function __construct(array $payload,
                                array $transactions = null,
                                array $states = null,
                                array $participants = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $participants, $id_, $version, $doc_uri);
        if ($transactions) {
            foreach ($transactions as $txn) {
                if (is_array($txn)) {
                    $txn = new Transaction($txn);
                }
                if (!$txn->has_metadata()) {
                    throw new SiriusContextError('Transaction must have metadata for specific Ledger');
                }
                $this->payload['transactions'] = $transactions;
            }
        }
        if ($states) {
            $fixed_states = [];
            foreach ($states as $state) {
                array_push($fixed_states, new MicroLedgerState($state));
            }
            sort($fixed_states);
            $accum = hash('sha256', 'Message');
            /** @var MicroLedgerState $state */
            foreach ($fixed_states as $state) {
                $accum = $accum . $state->hash;
            }
            $this->payload['hash'] = $accum;
        }
    }

    public function getThreadId()
    {
        $thread = ArrayHelper::getValueWithKeyFromArray(self::THREAD_DECORATOR, $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('thid', $thread);
    }

    public function getTransactions()
    {
        $txns = ArrayHelper::getValueWithKeyFromArray('transactions', $this->payload);
        if ($txns) {
            $res = [];
            foreach ($txns as $txn) {
                if (is_array($txn)) {
                    new Transaction($txn);
                }
                array_push($res, $txn);
            }
            return $res;
        } else {
            return null;
        }
    }

    public function getHash()
    {
        return ArrayHelper::getValueWithKeyFromArray('hash', $this->payload);
    }
}