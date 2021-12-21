<?php


namespace Siruis\Agent\Microledgers;


abstract class AbstractMicroledgerList
{
    abstract public function create(string $name, array $genesis);

    abstract public function ledger(string $name): AbstractMicroledger;

    abstract public function reset(string $name);

    abstract public function is_exists(string $name);

    abstract public function leaf_hash($txn);

    abstract public function list();

    abstract public function batched(): ?AbstractBatchedAPI;
}