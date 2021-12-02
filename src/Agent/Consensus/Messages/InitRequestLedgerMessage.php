<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;

class InitRequestLedgerMessage extends BaseInitLedgerMessage
{
    public $NAME = 'initialize-request';
    public $timeout_sec;
    public $thread_id;

    public function __construct(array $payload,
                                int $timeout_sec = null,
                                string $ledger_name = null,
                                array $genesis = null,
                                string $root_hash = null,
                                array $participants = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $ledger_name, $genesis, $root_hash, $participants, $id_, $version, $doc_uri);
        if ($timeout_sec) {
            $this->timeout_sec = $timeout_sec;
        }
        $this->thread_id = $this->getThreadId();
    }

    public function getThreadId(): ?string
    {
        $thread = ArrayHelper::getValueWithKeyFromArray(self::THREAD_DECORATOR, $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('thid', $thread);
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \SodiumException
     */
    public function add_signature(AbstractCrypto $api, Me $me): void
    {
        if (!in_array($me->did, $this->getParticipants(), true)) {
            throw new SiriusContextError('Signer must be a participant');
        }
        if ($this->ledger_hash) {
            $hash_signature = Utils::sign($api, $this->ledger_hash, $me->verkey);
            $signatures = [];
            foreach ($this->getSignatures() as $signature) {
                if ($signature['participant'] !== $me->did) {
                    $signatures[] = $signature;
                }
            }
            $signatures[] = [
                'participant' => $me->did,
                'signature' => $hash_signature
            ];
            $this->setSignatures($signatures);
        } else {
            throw new SiriusContextError('Ledger Hash description is empty');
        }
    }

    public function check_ledger_hash()
    {
        if (!$this->ledger_hash) {
            throw new SiriusContextError('Ledger hash is empty');
        }
        if (!$this->ledger) {
            throw new SiriusContextError('Ledger body is empty');
        }
    }

    public function validate()
    {
        parent::validate();
        if (!$this->ledger) {
            throw new SiriusValidationError('Ledger info is empty');
        }
        foreach (['root_hash', 'name', 'genesis'] as $expect_field) {
            if (!key_exists($expect_field, $this->ledger)) {
                throw new SiriusValidationError('Expected field '.$expect_field.' does not exists in Ledger container');
            }
        }
        if (!$this->ledger_hash) {
            throw new SiriusValidationError('Ledger Hash info is empty');
        }
        foreach (['func', 'base58'] as $expect_field) {
            if (!key_exists($expect_field, $this->ledger_hash)) {
                throw new SiriusValidationError('Expected field '.$expect_field.' does not exists in Ledger Hash');
            }
        }
    }
}