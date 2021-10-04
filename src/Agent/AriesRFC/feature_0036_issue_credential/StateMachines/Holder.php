<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\StateMachines;


use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\ProposedAttrib;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;
use Siruis\Encryption\Encryption;
use Siruis\Errors\IndyExceptions\WalletItemNotFound;
use Siruis\Hub\Init;

/**
 * Implementation of Holder role for Credential-issuing protocol
 * @see https://github.com/hyperledger/aries-rfcs/tree/master/features/0036-issue-credential
 */
class Holder extends BaseIssuingStateMachine
{
    /**
     * @var Pairwise
     */
    protected $issuer;

    public function __construct(Pairwise $issuer, int $time_to_live = 60, $logger = null)
    {
        parent::__construct($time_to_live, $logger);
        $this->issuer = $issuer;
    }

    public function accept(

    )
    {

    }

    public function _is_leader(): bool
    {
        return false;
    }

    public function _store_credential(
        array $cred_metadata, array $cred, array $cred_def, ?array $rev_reg_def, ?string $cred_id
    )
    {
        try {
            $cred_older = Init::AnonCreds()->prover_get_credential($cred_id);
        } catch (WalletItemNotFound $exception) {
            $cred_older = null;
        }
        if ($cred_older) {
            // Delete older credential
            Init::AnonCreds()->prover_delete_credential($cred_id);
        }
        return Init::AnonCreds()->prover_store_credential(
            $cred_id,
            $cred_metadata,
            $cred,
            $cred_def,
            $rev_reg_def
        );
    }

    /**
     * @param string $cred_id
     * @param ProposedAttrib[] $preview
     * @throws \SodiumException
     */
    public static function _store_mime_types(string $cred_id, array $preview)
    {
        if (!is_null($preview)) {
            $mime_types = [];
            foreach ($preview as $prop_attrib) {
                if (key_exists('mime-type', $prop_attrib->data)) {
                    array_push($mime_types, [$prop_attrib->data['name'] => $prop_attrib['mime-type']]);
                }
            }
            if (count($mime_types) > 0) {
                $record = self::get_mime_types($cred_id);
                if ($record) {
                    Init::NonSecrets()->delete_wallet_record('mime-types', $cred_id);
                }
                Init::NonSecrets()->add_wallet_record('mime-types', $cred_id, Encryption::bytes_to_b64(json_encode($mime_types)));
            }
        }
    }

    /**
     * @param string $cred_id
     * @return array|mixed
     * @throws \SodiumException
     */
    public static function get_mime_types(string $cred_id)
    {
        try {
            $record = Init::NonSecrets()->get_wallet_record('mime-types', $cred_id, new RetrieveRecordOptions(true, true, false));
        } catch (WalletItemNotFound $exception) {
            $record = null;
        }
        if (!is_null($record)) {
            return json_decode(Encryption::b64_to_bytes($record['value']));
        } else {
            return [];
        }
    }
}