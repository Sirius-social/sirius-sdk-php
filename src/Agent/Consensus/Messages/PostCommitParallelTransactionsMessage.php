<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;
use Siruis\Helpers\StringHelper;

class PostCommitParallelTransactionsMessage extends BaseParallelTransactionsMessage
{
    public $NAME = 'stage-post-commit-parallel';

    public function getCommits()
    {
        $payload = ArrayHelper::getValueWithKeyFromArray('commits', $this->payload, []);
        if (count($payload)) {
            return $payload;
        } else {
            return [];
        }
    }

    public function extendCommits(array $commits)
    {
        array_merge($this->payload['commits'], $commits);
    }

    public function add_commit_sign(AbstractCrypto $api, CommitParallelTransactionsMessage $commit, Me $me)
    {
        $signed = Utils::sign($api, $commit, $me->verkey);
        $commits = $this->getCommits();
        array_push($commits, $signed);
        $this->payload['commits'] = $commits;
    }

    public function verify_commits(AbstractCrypto $api, CommitParallelTransactionsMessage $expected, array $verkeys)
    {
        $actual_verkeys = [];
        foreach ($this->getCommits() as $commit) {
            array_push($actual_verkeys, $commit['signer']);
        }
        $unique_verkeys = array_unique($verkeys);
        if (!isset($unique_verkeys)) {
            return false;
        }
        foreach ($this->getCommits() as $signed) {
            list($commit, $is_succes) = Utils::verify_signed($api, $signed);
            if ($is_succes) {
                $cleaned_commit = [];
                foreach ($commit->payload as $k => $v) {
                    if (!StringHelper::startsWith($k, '~')) {
                        array_push($cleaned_commit, [$k => $v]);
                    }
                }
                $cleaned_expect = [];
                foreach ($expected->payload as $k => $v) {
                    if (!StringHelper::startsWith($k, '~')) {
                        array_push($cleaned_expect, [$k => $v]);
                    }
                }
                if (count(array_diff($cleaned_commit, $cleaned_expect))) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    public function validate()
    {
        parent::validate();
        if (!$this->getCommits()) {
            throw new SiriusValidationError('Commits collection is empty');
        }
    }
}