<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Helpers\ArrayHelper;
use SodiumException;

/**
 * Message to accumulate participants signed accepts for transactions list in parallel mode
 * @package Siruis\Agent\Consensus\Messages
 */
class PreCommitParallelTransactionsMessage extends BaseParallelTransactionsMessage
{
    public $NAME = 'stage-pre-commit-parallel';

    public function sign_states(AbstractCrypto $api, Me $me)
    {
        $signed = Utils::sign($api, $this->getHash(), $me->verkey);
        $this->payload['hash~sig'] = $signed;
        if (key_exists('transactions', $this->payload)) {
            unset($this->payload['transactions']);
        }
    }

    public function verify_state(AbstractCrypto $api, string $expected_verkey)
    {
        $hash_signed = ArrayHelper::getValueWithKeyFromArray('hash~sig', $this->payload);
        if ($hash_signed) {
            if ($hash_signed['signer'] == $expected_verkey) {
                list($state_hash, $is_success) = Utils::verify_signed($api, $hash_signed);
                return [$is_success, $state_hash];
            } else {
                return [false, null];
            }
        } else {
            return [false, null];
        }
    }
}