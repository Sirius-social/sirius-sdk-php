<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Errors\Exceptions\SiriusValidationError;

/**
 * Message to commit transactions list in parallel mode
 * @package Siruis\Agent\Consensus\Messages
 */
class CommitParallelTransactionsMessage extends BaseParallelTransactionsMessage
{
    public $NAME = 'stage-commit-parallel';

    public function getPreCommits()
    {
        return $this->payload['pre_commits'] ?? [];
    }

    public function setPreCommits($value)
    {
        $this->payload['pre_commits'] = $value;
    }

    public function add_pre_commit(string $participant, PreCommitParallelTransactionsMessage $pre_commit)
    {
        if (!key_exists('hash~sig', $pre_commit->payload)) {
            throw new SiriusContextError('Pre-Commit for participant '.$participant.' does not have hash~sig attribute');
        }
        $pre_commits = $this->getPreCommits();
        $pre_commits[$participant] = $pre_commit->payload['hash~sig'];
        $this->setPreCommits($pre_commits);
        $participants = $this->getParticipants();
        array_push($participants, $participant);
        $this->setParticipants($participants);
    }

    public function validate()
    {
        parent::validate();
        if (!$this->getParticipants()) {
            throw new SiriusValidationError('Participants list is empty');
        }
        foreach ($this->getParticipants() as $participant) {
            if (!key_exists($participant, $this->getPreCommits())) {
                throw new SiriusValidationError('Pre-Commit for participant "'.$participant.'" does not exists');
            }
        }
    }

    public function verify_pre_commits(AbstractCrypto $api, string $expected_hash)
    {
        $states = [];
        foreach ($this->getPreCommits() as $participant => $signed) {
            list($state_hash, $is_success) = Utils::verify_signed($api, $signed);
            if (!$is_success) {
                throw new SiriusValidationError('Error verifying pre_commit for participant: '.$participant);
            }
            if ($state_hash != $expected_hash) {
                throw new SiriusValidationError('Ledger state for participant '.$participant.' is not consistent');
            }
            $states[$participant] = [$expected_hash, $signed];
        }
        return $states;
    }
}