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

    public function __construct(string $name, AgentRPC $api)
    {
        $this->name = $name;
        $this->api = $api;
        $this->state = null;
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
        $this->__check_state_is_exists();
        return $this->state['size'];
    }

    /**
     * @return int
     * @throws SiriusContextError
     */
    public function getUncommittedSize(): int
    {
        $this->__check_state_is_exists();
        return $this->state['uncommitted_size'];
    }

    /**
     * @return string
     * @throws SiriusContextError
     */
    public function getRootHash(): string
    {
        $this->__check_state_is_exists();
        return $this->state['root_hash'];
    }

    /**
     * @return string
     * @throws SiriusContextError
     */
    public function getUncommittedRootHash(): string
    {
        $this->__check_state_is_exists();
        return $this->state['uncommitted_root_hash'];
    }

    /**
     * @return int
     * @throws SiriusContextError
     */
    public function getSeqNo(): int
    {
        $this->__check_state_is_exists();
        return $this->state['seqNo'];
    }

    public function reload()
    {
        $state = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/state',
            [
                'name' => $this->name
            ]
        );
        $this->state = $state;
    }

    public function rename(string $new_name)
    {
        return $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/rename',
            [
                'name' => $this->name,
                'new_name' => $new_name
            ]
        );
    }

    /**
 9a9n57ey    * @param array $genesis
     * @return mixed
     * @throws SiriusContextError
     */
    public function init(array $genesis)
    {
        $remoteCallResult = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/initialize',
            [
                'name' => $this->name,
                'genesis_txns' => $genesis
            ]
        );
        $this->state = $remoteCallResult[0];
        $txns = $remoteCallResult[1];
        $result = [];
        foreach ($txns as $txn) {
            $txn = Transaction::create($txn);
            array_push($result, $txn->as_object());
        }
        return $result;
    }

    /**
     * @param array $transactions
     * @param null $txn_time
     * @return array
     * @throws SiriusContextError
     */
    public function append(array $transactions, $txn_time = null): array
    {
        $transactions_to_append = [];
        foreach ($transactions as $txn) {
            if ($txn instanceof Transaction) {
                array_push($transactions_to_append, $txn);
            } elseif (is_array($txn)) {
                $txn = Transaction::create($txn);
                array_push($transactions_to_append, $txn->as_object());
            } else {
                throw new RuntimeException('Unexpected transaction type');
            }
        }
        $transactions_with_meta = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/append_txns_metadata',
            [
                'name' => $this->name,
                'txns' => $transactions_to_append,
                'txn_time' => $txn_time
            ]
        );
        list($this->state, $start, $end, $appended_txns) = $this->api->remoteCall(
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
     * @throws SiriusContextError
     */
    public function commit(int $count)
    {
        list($this->state, $start, $end, $committed_txns) = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/commit_txns',
            [
                'name' => $this->name,
                'count' => $count
            ]
        );
        return [$start, $end, $committed_txns];
    }

    public function discard(int $count)
    {
        $this->state = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/discard_txns',
            [
                'name' => $this->name,
                'count' => $count
            ]
        );
    }

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

    public function reset_uncommitted()
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
     * @return Transaction
     * @throws SiriusContextError
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
     * @return Transaction
     * @throws SiriusContextError
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
     * @return Transaction
     * @throws SiriusContextError
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
     * @throws SiriusContextError
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
            array_push($ts, $t[1]);
        }
        return Transaction::from_value($ts);
    }

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
            array_push($result, new Transaction($txn));
        }
        return $result;
    }

    /**
     * @throws SiriusContextError
     */
    public function __check_state_is_exists()
    {
        if (!$this->state)
            throw new SiriusContextError('Load state of Microledger at First!');
    }
}