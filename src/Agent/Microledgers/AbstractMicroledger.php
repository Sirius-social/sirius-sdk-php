<?php


namespace Siruis\Agent\Microledgers;


abstract class AbstractMicroledger
{
    public abstract function getName(): string;

    public abstract function getSize(): int;

    public abstract function getUncommittedSize(): int;

    public abstract function getRootHash(): string;

    public abstract function getUncommittedRootHash(): string;

    public abstract function getSeqNo(): int;

    public abstract function reload();

    public abstract function rename(string $new_name);

    public abstract function init(array $genesis);

    public abstract function append(array $transactions, $txn_time = null);

    public abstract function commit(int $count);

    public abstract function discard(int $count);

    public abstract function merkle_info(int $seq_no): MerkleInfo;

    public abstract function audit_proof(int $seq_no): AuditProof;

    public abstract function reset_uncommitted();

    public abstract function get_transaction(int $seq_no): Transaction;

    public abstract function get_uncommitted_transaction(int $seq_no): Transaction;

    public abstract function get_last_transaction(): Transaction;

    public abstract function get_last_committed_transaction(): Transaction;

    public abstract function get_all_transactions(): array;

    public abstract function get_uncommitted_transactions(): array;
}