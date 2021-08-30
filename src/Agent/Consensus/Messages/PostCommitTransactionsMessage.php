<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;

class PostCommitTransactionsMessage extends BaseTransactionsMessage
{
    public $NAME = 'stage-post-commit';

    public function getCommits(): array
    {
        $payload = ArrayHelper::getValueWithKeyFromArray('commits', $this->payload);
        if ($payload) {
            return $payload;
        } else {
            return [];
        }
    }

    public function add_commit_sign(AbstractCrypto $api, CommitTransactionsMessage $commit, Me $me)
    {
        $signed = Utils::sign($api, $commit, $me->verkey);
        $commits = $this->getCommits();
        array_push($commits, $signed);
        $this->payload['commits'] = $commits;
    }

    public function verify_commits(AbstractCrypto $api, CommitTransactionsMessage $expected, array $verkeys)
    {
        $actual_verkeys = [];
        foreach ($this->getCommits() as $commit) {
            array_push($actual_verkeys, $commit['signer']);
        }
        foreach ($this->getCommits() as $signed) {
            $verify = Utils::verify_signed($api, $signed);
            $is_success = $verify[1];
            $commit = $verify[0];
            if ($is_success) {
                $cleaned_commit = [];
                $cleaned_expect = [];
                foreach ($commit as $cK => $cV) {
                    $kStart = substr($cK, 0, 1);
                    if ($kStart == '~') {
                        array_push($cleaned_commit, [$cK => $cV]);
                    }
                }
                foreach ($expected as $eK => $eV) {
                    if (substr($eK, 0, 1) == '~') {
                        array_push($cleaned_expect, [$eK => $eV]);
                    }
                }
                if ($cleaned_commit != $cleaned_expect) {
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