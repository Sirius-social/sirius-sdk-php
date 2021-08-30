<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Errors\Exceptions\SiriusValidationError;
use SodiumException;

class CommitTransactionsMessage extends BaseTransactionsMessage
{
    public $NAME = 'stage-commit';

    public function getPreCommits()
    {
        return $this->payload['pre_commits'] ? $this->payload['pre_commits'] : [];
    }

    /**
     * @param string $participant
     * @param PreCommitTransactionsMessage $pre_commit
     * @throws SiriusContextError
     */
    public function add_pre_commit(string $participant, PreCommitTransactionsMessage $pre_commit)
    {
        if (!$pre_commit->hash_sig) {
            throw new SiriusContextError('Pre-Commit for participant ' . $participant . ' does not have hash~sig attribute');
        }
        $pre_commits = $this->getPreCommits();
        $pre_commits[$participant] = $pre_commit['hash~sig'];
        $this->payload['pre_commits'] = $pre_commits;
    }

    public function validate()
    {
        parent::validate();
        foreach ($this->participants as $participant) {
            if (!key_exists($participant, $this->getPreCommits())) {
                throw new SiriusValidationError('Pre-Commit for participant ' . $participant . ' does not exists');
            }
        }
    }

    /**
     * @param AbstractCrypto $api
     * @param MicroLedgerState $expected_state
     * @return array
     * @throws SiriusValidationError
     * @throws SodiumException
     */
    public function verify_pre_commits(AbstractCrypto $api, MicroLedgerState $expected_state): array
    {
        $states = [];
        foreach ($this->getPreCommits() as $participant => $signed) {
            $verify = Utils::verify_signed($api, $signed);
            $is_success = $verify[1];
            $state_hash = $verify[0];
            if (!$is_success) {
                throw new SiriusValidationError('Error verifying pre_commit for participant: ' . $participant);
            }
            if ($state_hash != $expected_state->hash) {
                throw new SiriusValidationError('Ledger state for participant ' . $participant . ' is not consistent');
            }
            $states[$participant] = [$expected_state, $signed];
        }
        return $states;
    }
}