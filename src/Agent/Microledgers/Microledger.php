<?php


namespace Siruis\Agent\Microledgers;


use RuntimeException;
use Siruis\Agent\Connections\AgentRPC;
use Siruis\Errors\Exceptions\SiriusContextError;

class Microledger extends AbstractMicroledger
{
    public $name;
    public $api;
    public $state;

    public function __construct(string $name, AgentRPC $api, array $state = null)
    {
        $this->name = $name;
        $this->api = $api;
        $this->state = $state;
    }

    public function assign_to(AbstractMicroledger $other): void
    {
        if ($other instanceof self) {
            $other->state = $this->state;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     * @throws SiriusContextError
     */
    public function getSize(): int
    {
        $this->check_state_is_exists();
        return $this->state['size'];
    }

    /**
     * @return int
     * @throws SiriusContextError
     */
    public function getUncommittedSize(): int
    {
        $this->check_state_is_exists();
        return $this->state['uncommitted_size'];
    }

    /**
     * @return string
     * @throws SiriusContextError
     */
    public function getRootHash(): string
    {
        $this->check_state_is_exists();
        return $this->state['root_hash'];
    }

    /**
     * @return string
     * @throws SiriusContextError
     */
    public function getUncommittedRootHash(): string
    {
        $this->check_state_is_exists();
        return $this->state['uncommitted_root_hash'];
    }

    /**
     * @return int
     * @throws SiriusContextError
     */
    public function getSeqNo(): int
    {
        $this->check_state_is_exists();
        return $this->state['seqNo'];
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function reload(): void
    {
        $state = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/state',
            [
                'name' => $this->name
            ]
        );
        $this->state = $state;
    }

    /**
     * @param string $new_name
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function rename(string $new_name): void
    {
        $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/rename',
            [
                'name' => $this->name,
                'new_name' => $new_name
            ]
        );
        $this->name = $new_name;
    }

    /**
     * @param array $genesis
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function init(array $genesis): array
    {
        [$this->state, $txns] = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/initialize',
            [
                'name' => $this->name,
                'genesis_txns' => $genesis
            ]
        );
        $result = [];
        foreach ($txns as $txn) {
            $txn = Transaction::create($txn);
            $result[] = $txn;
        }
        return $result;
    }

    /**
     * @param array $transactions
     * @param $txn_time
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function append(array $transactions, $txn_time = null): array
    {
        $transactions_to_append = [];
        foreach ($transactions as $txn) {
            if ($txn instanceof Transaction) {
                $transactions_to_append[] = $txn->as_object();
            } elseif (is_array($txn)) {
                $txn = Transaction::create($txn);
                $transactions_to_append[] = $txn->as_object();
            } else {
                throw new RuntimeException('Unexpected transaction type');
            }
        }
        $transactions_with_meta = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/append_txns_metadata',
            [
                'name' => $this->getName(),
                'txns' => $transactions_to_append,
                'txn_time' => $txn_time
            ]
        );
        [$this->state, $start, $end, $appended_txns] = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/append_txns',
            [
                'name' => $this->name,
                'txns' => $transactions_with_meta
            ]
        );
        return [$start, $end, $appended_txns];
    }

    /**
     * @param int $count
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function commit(int $count): array
    {
        [$this->state, $start, $end, $committed_txns] = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/commit_txns',
            [
                'name' => $this->name,
                'count' => $count
            ]
        );
        return [$start, $end, $committed_txns];
    }

    /**
     * @param int $count
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function discard(int $count): void
    {
        $this->state = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/discard_txns',
            [
                'name' => $this->name,
                'count' => $count
            ]
        );
    }

    /**
     * @param int $seq_no
     * @return \Siruis\Agent\Microledgers\MerkleInfo
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function merkle_info(int $seq_no): MerkleInfo
    {
        $merkle_info = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/merkle_info',
            [
                'name' => $this->name,
                'seqNo' => $seq_no
            ]
        );
        return new MerkleInfo(
            $merkle_info['rootHash'],
            $merkle_info['auditPath']
        );
    }

    /**
     * @param int $seq_no
     * @return \Siruis\Agent\Microledgers\AuditProof
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function audit_proof(int $seq_no): AuditProof
    {
        $proof = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/audit_proof',
            [
                'name' => $this->name,
                'seqNo' => $seq_no
            ]
        );
        return new AuditProof(
            $proof['rootHash'],
            $proof['auditPath'],
            $proof['ledgerSize']
        );
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function reset_uncommitted(): void
    {
        $this->state = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/reset_uncommitted',
            [
                'name' => $this->name
            ]
        );
    }

    /**
     * @param int $seq_no
     * @return \Siruis\Agent\Microledgers\Transaction
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_transaction(int $seq_no): Transaction
    {
        $txn = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/get_by_seq_no',
            [
                'name' => $this->name,
                'seqNo' => $seq_no
            ]
        );
        return new Transaction($txn);
    }

    /**
     * @param int $seq_no
     * @return \Siruis\Agent\Microledgers\Transaction
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_uncommitted_transaction(int $seq_no): Transaction
    {
        $txn = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/get_by_seq_no_uncommitted',
            [
                'name' => $this->name,
                'seqNo' => $seq_no
            ]
        );
        return new Transaction($txn);
    }

    /**
     * @return \Siruis\Agent\Microledgers\Transaction
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_last_transaction(): Transaction
    {
        $txn = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/get_last_txn',
            [
                'name' => $this->name
            ]
        );
        return new Transaction($txn);
    }

    /**
     * @return \Siruis\Agent\Microledgers\Transaction
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_last_committed_transaction(): Transaction
    {
        $txn = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/get_last_committed_txn',
            [
                'name' => $this->name
            ]
        );
        return new Transaction($txn);
    }

    /**
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_all_transactions(): array
    {
        $txns = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/get_all_txns',
            [
                'name' => $this->name
            ]
        );
        $ts = [];
        foreach ($txns as $t) {
            $ts[] = new Transaction($t[1]);
        }
        return $ts;
    }

    /**
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function get_uncommitted_transactions(): array
    {
        $txns = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/get_uncommitted_txns',
            [
                'name' => $this->name
            ]
        );
        $result = [];
        foreach ($txns as $txn) {
            $result[] = new Transaction($txn);
        }
        return $result;
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     */
    public function check_state_is_exists(): void
    {
        if (!$this->state) {
            throw new SiriusContextError('Load state of Microledger at First!');
        }
    }
}