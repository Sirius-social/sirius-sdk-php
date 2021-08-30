<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;

/**
 * Message to process parallel transactions propose by Actor
 * @package Siruis\Agent\Consensus\Messages
 */
class ProposeParallelTransactionsMessage extends BaseParallelTransactionsMessage
{
    public $NAME = 'stage-propose-parallel';

    public function __construct(array $payload,
                                array $transactions = null,
                                array $states = null,
                                array $participants = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null,
                                int $timeout_sec = null)
    {
        parent::__construct($payload, $transactions, $states, $participants, $id_, $version, $doc_uri);
        if ($timeout_sec) {
            $this->payload['timeout_sec'] = $timeout_sec;
        }
        if ($states) {
            $sorted_states = [];
            foreach ($states as $state) {
                array_push($sorted_states, new MicroLedgerState($state));
            }
            $names = [];
            foreach ($sorted_states as $sorted_state) {
                array_push($names, $sorted_state->name);
            }
            $this->payload['ledgers'] = $names;
        }
    }

    public function getLedgers()
    {
        return ArrayHelper::getValueWithKeyFromArray('ledgers', $this->payload, []);
    }

    public function getTimeoutSec()
    {
        return ArrayHelper::getValueWithKeyFromArray('timeout_sec', $this->payload);
    }

    public function validate()
    {
        parent::validate();
        if (!$this->getTransactions()) {
            throw new SiriusValidationError('Empty transactions list');
        }
        foreach ($this->getTransactions() as $txn) {
            if (!$txn->has_metadata()) {
                throw new SiriusValidationError('Transaction must have metadata');
            }
        }
        if (!$this->getLedgers()) {
            throw new SiriusValidationError('Ledgers is empty');
        }
        if (!$this->getHash()) {
            throw new SiriusValidationError('Empty hash');
        }
    }
}