<?php


namespace Siruis\Tests;


use PHPUnit\Framework\TestCase;
use Siruis\Agent\Microledgers\AbstractMicroledger;
use Siruis\Agent\Microledgers\Microledger;
use Siruis\Agent\Microledgers\Transaction;
use Siruis\Tests\Helpers\Conftest;

class TestMicroledgers extends TestCase
{
    public function get_state(AbstractMicroledger $ledger): array
    {
        return [
            'name' => $ledger->name,
            'seq_no' => $ledger->getSeqNo(),
            'size' => $ledger->getSize(),
            'uncommitted_size' => $ledger->getUncommittedSize(),
            'root_hash' => $ledger->getRootHash(),
            'uncommitted_root_hash' => $ledger->getUncommittedRootHash()
        ];
    }

    public function test_init_ledger()
    {
        $ledger_name = Conftest::ledger_name();
        $agent = Conftest::agent4();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'],
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
            ];
            /** @var Microledger $ledger */
            list($ledger, $txns) = $agent->microledgers->create($ledger_name, $genesis_txns);
            self::assertEquals('3u8ZCezSXJq72H5CdEryyTuwAKzeZnCZyfftJVFr7y8U', $ledger->getRootHash());
            foreach ($txns as $txn) {
                self::assertInstanceOf(Transaction::class, $txn);
            }
        } finally {
            $agent->close();
        }
    }
}