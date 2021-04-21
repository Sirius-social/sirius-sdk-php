<?php


namespace Siruis\Agent\Microledgers;


abstract class AbstractBatchedAPI
{
    public abstract function open($ledgers): array;

    public abstract function close();

    public abstract function states(): array;

    public abstract function append($transactions, $txn_time = null): array;

    public abstract function commit(): array;

    public abstract function reset_uncommitted(): array;
}