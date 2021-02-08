<?php


namespace Siruis\Agent\Microledgers;


abstract class AbstractMicroledgerList
{
    public abstract function create(string $name, array $genesis);

    public abstract function ledger(string $name): AbstractMicroledger;

    public abstract function reset(string $name);

    public abstract function is_exists(string $name);

    public abstract function leaf_hash($txn);

    public abstract function list();
}