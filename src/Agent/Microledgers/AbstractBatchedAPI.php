<?php


namespace Siruis\Agent\Microledgers;


abstract class AbstractBatchedAPI
{
    abstract public function open($ledgers): array;

    abstract public function close();

    abstract public function states(): array;

    abstract public function append($transactions, $txn_time = null): array;

    abstract public function commit(): array;

    abstract public function reset_uncommitted(): array;
}