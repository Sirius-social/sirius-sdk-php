<?php


namespace Siruis\Agent\Microledgers;


abstract class AbstractMicroledger
{
    abstract public function getName(): string;

    abstract public function getSize(): int;

    abstract public function getUncommittedSize(): int;

    abstract public function getRootHash(): string;

    abstract public function getUncommittedRootHash(): string;

    abstract public function getSeqNo(): int;

    abstract public function reload();

    abstract public function rename(string $new_name);

    abstract public function init(array $genesis);

    abstract public function append(array $transactions, $txn_time = null);

    abstract public function commit(int $count);

    abstract public function discard(int $count);

    abstract public function merkle_info(int $seq_no): MerkleInfo;

    abstract public function audit_proof(int $seq_no): AuditProof;

    abstract public function reset_uncommitted();

    abstract public function get_transaction(int $seq_no): Transaction;

    abstract public function get_uncommitted_transaction(int $seq_no): Transaction;

    abstract public function get_last_transaction(): Transaction;

    abstract public function get_last_committed_transaction(): Transaction;

    abstract public function get_all_transactions(): array;

    abstract public function get_uncommitted_transactions(): array;
}