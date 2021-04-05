<?php


namespace Siruis\Tests;


use PHPUnit\Framework\TestCase;
use Siruis\Agent\Microledgers\AbstractMicroledger;
use Siruis\Agent\Microledgers\LedgerMeta;
use Siruis\Agent\Microledgers\MerkleInfo;
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
        } finally {
            $agent->close();
        }
    }

    public function test_merkle_info()
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'],
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
                ['reqId' => 4, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op4'],
                ['reqId' => 5, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op5'],
            ];
            list($ledger, $txns) = $agent->microledgers->create($ledger_name, $genesis_txns);
            /** @var MerkleInfo $merkle_info */
            $merkle_info = $ledger->merkle_info(4);
            self::assertEquals('CwX1TRYKpejHmdnx3gMgHtSioDzhDGTASAD145kjyyRh', $merkle_info->root_hash);
            self::assertEquals(['46kxvYf7RjRERXdS56vUpFCzm2A3qRYSLaRr6tVT6tSd', '3sgNJmsXpmin7P5C6jpHiqYfeWwej5L6uYdYoXTMc1XQ'], $merkle_info->audit_path);
        } finally {
            $agent->close();
        }
    }

    public function test_append_operations()
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1']
            ];
            list($ledger, $_) = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txns = [
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
            ];
            $txn_time = (string)date('Y-m-d h:i:s', time());
            list($start, $end, $appended_transactions) = $ledger->append($txns, $txn_time);
            self::assertEquals(3, $end);
            self::assertEquals(2, $start);
        } finally {
            $agent->close();
        }
    }

    public function test_commit_discard()
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1']
            ];
            /** @var Microledger $ledger */
            list($ledger, $_) = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txns = [
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
            ];
            $txn_time = (string)date('Y-m-d h:i:s', time());

            self::assertEquals($ledger->getUncommittedRootHash(), $ledger->getRootHash());
            $ledger->append($txns, $txn_time);
            self::assertNotEquals($ledger->getUncommittedRootHash(), $ledger->getRootHash());
            self::assertEquals(1, $ledger->getSize());
            self::assertEquals(3, $ledger->getUncommittedSize());

            // COMMIT
            $ledger->commit(1);
            self::assertEquals(2, $ledger->getSize());
            self::assertEquals(3, $ledger->getUncommittedSize());
            self::assertNotEquals($ledger->getRootHash(), $ledger->getUncommittedRootHash());

            // DISCARD
            $ledger->discard(1);
            self::assertEquals(2, $ledger->getSize());
            self::assertEquals(2, $ledger->getUncommittedSize());
            self::assertEquals($ledger->getRootHash(), $ledger->getUncommittedRootHash());
        } finally {
            $agent->close();
        }
    }

    public function test_reset_uncommitted()
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1']
            ];
            /** @var Microledger $ledger */
            list($ledger, $_) = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txns = [
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3']
            ];
            $ledger->append($txns);
            $uncommitted_size_before = $ledger->getUncommittedSize();
            $ledger->reset_uncommitted();
            $uncommitted_size_after = $ledger->getUncommittedSize();

            self::assertNotEquals($uncommitted_size_before, $uncommitted_size_after);
            self::assertEquals(1, $uncommitted_size_after);
        } finally {
            $agent->close();
        }
    }

    public function test_get_operations()
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'],
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
            ];
            /** @var Microledger $ledger */
            list($ledger, $_) = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txns = [
                ['reqId' => 4, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op4'],
                ['reqId' => 5, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op5'],
            ];
            $ledger->append($txns);

            // 1 get_last_committed_txn
            $txn = $ledger->get_last_committed_transaction();
            self::assertInstanceOf(Transaction::class, $txn);
            self::assertStringContainsString('op3', $txn->as_json());

            // 2 get_last_txn
            $txn = $ledger->get_last_transaction();
            self::assertInstanceOf(Transaction::class, $txn);
            self::assertStringContainsString('op5', $txn->as_json());

            // 3 get_uncommitted_txns
            $txns = $ledger->get_uncommitted_transactions();
            foreach ($txns as $txn) {
                self::assertInstanceOf(Transaction::class, $txn);
                self::assertThat($txn->as_json(), self::logicalOr(self::stringContains('op4'), self::stringContains('op5')));
            }

            // 4 get_by_seq_no
            $txn = $ledger->get_transaction(1);
            self::assertInstanceOf(Transaction::class, $txn);
            self::assertStringContainsString('op1', $txn->as_json());

            // 5 get_by_seq_no_uncommitted
            $txn = $ledger->get_uncommitted_transaction(4);
            self::assertInstanceOf(Transaction::class, $txn);
            self::assertStringContainsString('op4', $txn->as_json());
        } finally {
            $agent->close();
        }
    }

    public function test_reset()
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'],
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
                ['reqId' => 4, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op4'],
                ['reqId' => 5, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op5'],
            ];
            list($ledger, $_) = $agent->microledgers->create($ledger_name, $genesis_txns);
            self::assertEquals(5, $ledger->getSize());

            $is_exists = $agent->microledgers->is_exists($ledger_name);
            self::assertTrue($is_exists);

            $agent->microledgers->reset($ledger_name);
            $is_exists = $agent->microledgers->is_exists($ledger_name);
            self::assertFalse($is_exists);
        } finally {
            $agent->close();
        }
    }

    public function test_list()
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'],
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
            ];
            list($ledger, $_) = $agent->microledgers->create($ledger_name, $genesis_txns);
            // Get list
            $collection = $agent->microledgers->list();
            self::assertStringContainsString($ledger_name, implode(', ', $collection));
            foreach ($collection as $meta) {
                self::assertInstanceOf(LedgerMeta::class, $meta);
            }

            // Is exists
            $ok = $agent->microledgers->is_exists($ledger_name);
            self::assertTrue($ok);

            // Reset Calling
            $agent->microledgers->reset($ledger_name);

            // Get List
            $collection = $agent->microledgers->list();
            self::assertStringNotContainsString($ledger_name, implode(', ', $collection));
        } finally {
            $agent->close();
        }
    }
}