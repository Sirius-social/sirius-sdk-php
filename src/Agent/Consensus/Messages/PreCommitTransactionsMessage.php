<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use SodiumException;

class PreCommitTransactionsMessage extends BaseTransactionsMessage
{
    public $NAME = 'stage-pre-commit';
    public $hash_sig;

    /**
     * @param AbstractCrypto $api
     * @param Me $me
     * @throws SodiumException
     */
    public function sign_state(AbstractCrypto $api, Me $me)
    {
        $signed = Utils::sign($api, $this->hash, $me->verkey);
        $this->hash_sig = $signed;
        unset($this->state);
    }

    public function verify_state(AbstractCrypto $api, string $expected_verkey): array
    {
        $hash_signed = $this->hash_sig ? $this->hash_sig : null;
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