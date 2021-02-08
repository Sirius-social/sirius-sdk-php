<?php


namespace Siruis\Agent\Microledgers;


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
    }

    public function rename(string $new_name)
    {
        // TODO: Implement rename() method.
    }

    public function init(array $genesis)
    {
        // TODO: Implement init() method.
    }

    public function append(array $transactions, $txn_time = null)
    {
        // TODO: Implement append() method.
    }

    public function commit(int $count)
    {
        // TODO: Implement commit() method.
    }

    public function discard(int $count)
    {
        // TODO: Implement discard() method.
    }

    public function merkle_info(int $seq_no): MerkleInfo
    {
        // TODO: Implement merkle_info() method.
    }

    public function audit_proof(int $seq_no): AuditProof
    {
        // TODO: Implement audit_proof() method.
    }

    public function reset_uncommitted()
    {
        // TODO: Implement reset_uncommitted() method.
    }

    public function get_transaction(int $seq_no): Transaction
    {
        // TODO: Implement get_transaction() method.
    }

    public function get_uncommitted_transaction(int $seq_no): Transaction
    {
        // TODO: Implement get_uncommitted_transaction() method.
    }

    public function get_last_transaction(): Transaction
    {
        // TODO: Implement get_last_transaction() method.
    }

    public function get_last_committed_transaction(): Transaction
    {
        // TODO: Implement get_last_committed_transaction() method.
    }

    public function get_all_transactions(): array
    {
        // TODO: Implement get_all_transactions() method.
    }

    public function get_uncommitted_transactions(): array
    {
        // TODO: Implement get_uncommitted_transactions() method.
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