<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Microledgers\Microledgers;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;
use SodiumException;

class BaseInitLedgerMessage extends SimpleConsensusMessage
{
    public $NAME = 'initialize';
    public $ledger;
    public $ledger_hash;
    public $signatures;

    public function __construct(array $payload,
                                string $ledger_name = null,
                                array $genesis = null,
                                string $root_hash = null,
                                array $participants = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $participants, $id_, $version, $doc_uri);
        $ledger = [];
        if ($ledger_name) {
            $ledger['name'] = $ledger_name;
        }
        if ($root_hash) {
            $ledger['root_hash'] = $root_hash;
        }
        if ($genesis) {
            $ledger['genesis'] = $genesis;
        }
        if ($ledger) {
            $this->ledger = $ledger;
            $data = Microledgers::serialize_ordering($ledger);
            $this->ledger_hash = [
                'func' => 'sha256',
                'base58' => Encryption::bytes_to_b58($data)
            ];
        }
        $this->signatures = $this->getSignatures();
    }

    public function getSignatures()
    {
        return ArrayHelper::getValueWithKeyFromArray('signatures', $this->payload, []);
    }

    public function setSignatures($value)
    {
        $this->payload['signatures'] = $value;
        $this->signatures = $this->getSignatures();
    }

    /**
     * @param AbstractCrypto $api
     * @param string $participant
     * @return array
     * @throws SiriusContextError
     * @throws SiriusValidationError
     * @throws SodiumException
     */
    public function check_signatures(AbstractCrypto $api, string $participant = 'ALL'): array
    {
        if (!$this->ledger_hash) {
            throw new SiriusContextError('Ledger Hash description is empty');
        }
        $signatures = [];
        if ($participant == 'ALL') {
            $signatures = $this->getSignatures();
        } else {
            foreach ($this->getSignatures() as $signature) {
                if ($signature['participant'] == $participant) {
                    array_push($signatures, $signature);
                }
            }
        }
        if ($signatures) {
            $response = [];
            foreach ($signatures as $item) {
                list($signed_ledger_hash, $is_success) = Utils::verify_signed($api, $item['signature']);
                if (!$is_success) {
                    throw new SiriusValidationError('Invalid sign for participant '. $item['participant']);
                }
                if ($signed_ledger_hash != $this->ledger_hash) {
                    throw new SiriusValidationError('NonConsistent ledger hash for participant ' . $item['participant']);
                }
                $response[$item['participant']] = $signed_ledger_hash;
            }
            return $response;
        } else {
            throw new SiriusContextError('Signatures list is empty');
        }
    }
}