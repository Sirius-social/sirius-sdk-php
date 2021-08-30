<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Errors\Exceptions\SiriusValidationError;

class ProposeTransactionsMessage extends BaseTransactionsMessage
{
    public $NAME = 'stage-propose';
    public $timeout_sec;

    public function __construct(array $payload,
                                array $transactions = null,
                                $state = null,
                                array $participants = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null,
                                int $timeout_sec = null)
    {
        parent::__construct($payload, $transactions, $state, $participants, $id_, $version, $doc_uri);
        $this->timeout_sec = $timeout_sec;
    }

    public function validate()
    {
        parent::validate();
        if (!$this->transactions) {
            throw new SiriusValidationError('Empty transactions list');
        }
        foreach ($this->transactions as $txn) {
            if (!$txn->has_metadata()) {
                throw new SiriusValidationError('Transaction has not metadata');
            }
        }
        if (!$this->state) {
            throw new SiriusValidationError('Empty state');
        }
        if (!$this->hash) {
            throw new SiriusValidationError('Empty hash');
        }
    }
}