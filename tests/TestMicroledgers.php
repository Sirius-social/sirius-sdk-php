<?php


namespace Siruis\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use Siruis\Agent\Microledgers\AbstractMicroledger;
use Siruis\Agent\Microledgers\BatchedAPI;
use Siruis\Agent\Microledgers\LedgerMeta;
use Siruis\Agent\Microledgers\MerkleInfo;
use Siruis\Agent\Microledgers\Microledger;
use Siruis\Agent\Microledgers\Transaction;
use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusPromiseContextException;
use Siruis\Tests\Helpers\Conftest;
use stdClass;

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

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     */
    public function test_init_ledger(): void
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
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
            self::assertEquals('3u8ZCezSXJq72H5CdEryyTuwAKzeZnCZyfftJVFr7y8U', $ledger->getRootHash());
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     */
    public function test_merkle_info(): void
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
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
            /** @var MerkleInfo $merkle_info */
            $merkle_info = $ledger->merkle_info(4);
            self::assertEquals('CwX1TRYKpejHmdnx3gMgHtSioDzhDGTASAD145kjyyRh', $merkle_info->root_hash);
            self::assertEquals(['46kxvYf7RjRERXdS56vUpFCzm2A3qRYSLaRr6tVT6tSd', '3sgNJmsXpmin7P5C6jpHiqYfeWwej5L6uYdYoXTMc1XQ'], $merkle_info->audit_path);
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     */
    public function test_append_operations(): void
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1']
            ];
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txns = [
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
            ];
            $txn_time = (string)date('Y-m-d h:i:s');
            [$start, $end,] = $ledger->append($txns, $txn_time);
            self::assertEquals(3, $end);
            self::assertEquals(2, $start);
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_commit_discard(): void
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1']
            ];
            /** @var Microledger $ledger */
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txns = [
                ['reqId' => 2, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op2'],
                ['reqId' => 3, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op3'],
            ];
            $txn_time = (string)date('Y-m-d h:i:s');

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

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_reset_uncommitted(): void
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1']
            ];
            /** @var Microledger $ledger */
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
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

    /**
     * @return void
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_get_operations(): void
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
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
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

    /**
     * @return void
     */
    public function test_reset(): void
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
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
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

    /**
     * @return void
     */
    public function test_list(): void
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
            $agent->microledgers->create($ledger_name, $genesis_txns);
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

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \JsonException
     */
    public function test_get_all_txns(): void
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
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txns = [
                ['reqId' => 4, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op4'],
                ['reqId' => 5, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op5'],
            ];
            $ledger->append($txns);

            $txns = $ledger->get_all_transactions();
            foreach ($txns as $txn) {
                self::assertInstanceOf(Transaction::class, $txn);
                self::assertThat($txn->as_json(), self::logicalOr(self::stringContains('op1'), self::stringContains('op2'), self::stringContains('op3')));
            }
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_audit_proof(): void
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
                ['reqId' => 6, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op6'],
            ];
            /** @var Microledger $ledger */
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txns = [
                ['reqId' => 7, 'identifier' => '2btLJAAb1S3x6hZYdVyAePjqtQYi2ZBSRGy4569RZu8h', 'op' => 'op7'],
                ['reqId' => 8, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op8'],
                ['reqId' => 9, 'identifier' => 'CECeGXDi6EHuhpwz19uyjjEnsRGNXodFYqCRgdLmLRkt', 'op' => 'op9'],
            ];
            $ledger->append($txns);

            foreach ([1, 2, 3, 4, 5, 6] as $seq_no) {
                $audit_proof = $ledger->audit_proof($seq_no);
                self::assertEquals('3eDS4j8HgpAyRnuvfFG624KKvQBuNXKBenhqHmHtUgeq', $audit_proof->root_hash);
                self::assertEquals(6, $audit_proof->ledger_size);
            }

            foreach ([7, 8, 9] as $seq_no) {
                $audit_proof = $ledger->audit_proof($seq_no);
                self::assertEquals('3eDS4j8HgpAyRnuvfFG624KKvQBuNXKBenhqHmHtUgeq', $audit_proof->root_hash);
                self::assertEquals(6, $audit_proof->ledger_size);
                self::assertEquals('Dkoca8Af15uMLBHAqbddwqmpiqsaDEtKDoFVfNRXt44g', $ledger->getUncommittedRootHash());
            }
            printf('@');
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     */
    public function test_leaf_hash(): void
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'],
            ];
            [, $txns] = $agent->microledgers->create($ledger_name, $genesis_txns);
            $txn = $txns[0];
            $leaf_hash = $agent->microledgers->leaf_hash($txn);
            $leaf_hash_b58 = Encryption::bytes_to_b58($leaf_hash);
            self::assertIsString($leaf_hash);
            $expected_b58 = '9Cekj2hzePVyyCMELL9WvSAccaShLK3QKfWMBHvy4WzT';
            self::assertEquals($expected_b58, $leaf_hash_b58);
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_rename(): void
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'],
            ];
            /** @var Microledger $ledger */
            [$ledger,] = $agent->microledgers->create($ledger_name, $genesis_txns);

            $new_name = 'new_name_'.uniqid('', true);
            $ledger->rename($new_name);
            self::assertEquals($new_name, $ledger->name);

            $is_exists1 = $agent->microledgers->is_exists($ledger_name);
            $is_exists2 = $agent->microledgers->is_exists($new_name);
            self::assertFalse($is_exists1);
            self::assertTrue($is_exists2);
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_batched_ops(): void
    {
        $agent = Conftest::agent4();
        $ledger_names = Conftest::ledger_names();
        $agent->open();
        try {
            $genesis_txns = [
                ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1', 'txnMetadata' => new stdClass()],
            ];
            $reset_txns = [
                ['reqId' => 2, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op2', 'txnMetadata' => new stdClass()],
            ];
            $commit_txns = [
                ['reqId' => 3, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op3', 'txnMetadata' => new stdClass()],
            ];
            $txn_time = (string)date('Y-m-d h:i:s');
            foreach ($ledger_names as $ledger_name) {
                $agent->microledgers->create($ledger_name, $genesis_txns);
            }

            /** @var BatchedAPI $batched */
            $batched = $agent->microledgers->batched();
            $ledgers = $batched->open($ledger_names);
            try {
                foreach ($ledgers as $ledger) {
                    self::assertContains($ledger->name, $ledger_names);
                }
                // Fetch states
                $ledgers = $batched->states();
                $states_before = [];
                foreach ($ledgers as $ledger) {
                    $states_before[$ledger->name] = $this->get_state($ledger);
                }
                $states_before_keys = array_keys($states_before);
                sort($states_before_keys);
                sort($ledger_names);
                self::assertEquals($ledger_names, $states_before_keys);
                // Append
                $ledgers = $batched->append($reset_txns);
                $states_after_append = [];
                foreach ($ledgers as $ledger) {
                    $states_after_append[$ledger->name] = $this->get_state($ledger);
                }
                $states_after_append_keys = array_keys($states_after_append);
                sort($states_after_append_keys);
                sort($ledger_names);
                self::assertEquals($ledger_names, $states_after_append_keys);
                foreach ($states_after_append as $state_) {
                    self::assertEquals(2, $state_['uncommitted_size']);
                }
                // Reset uncommitted
                $ledgers = $batched->reset_uncommitted();
                $states_after_reset_uncommitted = [];
                foreach ($ledgers as $ledger) {
                    $states_after_reset_uncommitted[$ledger->name] = $this->get_state($ledger);
                }
                $states_after_reset_uncommitted_keys = array_keys($states_after_reset_uncommitted);
                sort($states_after_reset_uncommitted_keys);
                sort($ledger_names);
                self::assertEquals($ledger_names, $states_after_reset_uncommitted_keys);
                foreach ($states_after_reset_uncommitted as $state_) {
                    self::assertEquals(1, $state_['uncommitted_size']);
                }
                // Append + Commit
                $batched->append($commit_txns, $txn_time);
                $ledgers = $batched->commit();
                $states_after_commit = [];
                foreach ($ledgers as $ledger) {
                    $states_after_commit[$ledger->name] = $this->get_state($ledger);
                }
                $states_after_commit_keys = array_keys($states_after_commit);
                sort($states_after_commit_keys);
                sort($ledger_names);
                self::assertEquals($ledger_names, $states_after_commit_keys);
                foreach ($states_after_commit as $state_) {
                    self::assertEquals(2, $state_['uncommitted_size']);
                    self::assertEquals(2, $state_['size']);
                }
                // Check all txns
                foreach ($ledger_names as $ledger_name) {
                    /** @var Microledger $ledger */
                    $ledger = $agent->microledgers->ledger($ledger_name);
                    $txns = $ledger->get_all_transactions();
                    $txns_str = implode(', ', $txns);
                    self::assertStringContainsString('op1', $txns_str);
                    self::assertStringNotContainsString('op2', $txns_str);
                    self::assertStringContainsString('op3', $txns_str);
                    self::assertStringContainsString($txn_time, $txns_str);
                }
            } finally {
                $batched->close();
            }
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     */
    public function test_batched_ops_perfomance(): void
    {
        $agent = Conftest::agent4();
        $genesis_txns = [
            ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1', 'txnMetadata' => new stdClass()],
        ];
        $commit_txns = [
            ['reqId' => 2, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op2', 'txnMetadata' => new stdClass()],
        ];
        $agent->open();
        try {
            // Calc timeout for ledgers count = 2
            $ledger_names = Conftest::ledger_names();
            foreach ($ledger_names as $ledger_name) {
                $agent->microledgers->create($ledger_name, $genesis_txns);
            }
            $batched = $agent->microledgers->batched();
            $batched->open($ledger_names);
            try {
                $stamp1 = new DateTime();
                $batched->append($commit_txns);
                $batched->commit();
                $stamp2 = new DateTime();
            } finally {
                $batched->close();
            }
            $seconds_for_2 = date_diff($stamp2, $stamp1)->f;
            printf('========== Timeout for 2 Ledgers =======');
            printf("Seconds: $seconds_for_2");
            printf('========================================');
            // Calc timeout for ledgers count = 100
            $ledger_names = Conftest::ledger_names(100);
            foreach ($ledger_names as $ledger_name) {
                $agent->microledgers->create($ledger_name, $genesis_txns);
            }
            $batched = $agent->microledgers->batched();
            $batched->open($ledger_names);
            try {
                $stamp1 = new DateTime();
                $batched->append($commit_txns);
                $batched->commit();
                $stamp2 = new DateTime();
            } finally {
                $batched->close();
            }
            $seconds_for_100 = date_diff($stamp2, $stamp1)->f;
            printf('========== Timeout for 100 Ledgers =======');
            printf("Seconds: $seconds_for_100");
            printf('========================================');
            self::assertGreaterThan($seconds_for_100, 50 * $seconds_for_2);
            $ledger_names = Conftest::ledger_names(100);
            $ledgers = [];
            foreach ($ledger_names as $ledger_name) {
                [$ledger] = $agent->microledgers->create($ledger_name, $genesis_txns);
                $ledgers[] = $ledger;
            }
            $stamp1 = new DateTime();
            foreach ($ledgers as $ledger) {
                $ledger->append($commit_txns);
                $ledger->commit(count($commit_txns));
            }
            $stamp2 = new DateTime();
            $seconds_for_100_non_parallel = date_diff($stamp2, $stamp1)->f;
            printf('========== Timeout for 100 Ledgers Non-parallel mode =======');
            printf("Seconds: $seconds_for_100_non_parallel");
            printf('========================================');
            self::assertGreaterThan($seconds_for_100, $seconds_for_100_non_parallel / 2);
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     */
    public function test_microledgers_in_same_context_space_1(): void
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $genesis_txns = [
            ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1', 'txnMetadata' => new stdClass()],
        ];
        $commit_txns = [
            ['reqId' => 2, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op2', 'txnMetadata' => new stdClass()],
        ];
        $agent->open();
        try {
            $agent->microledgers->create($ledger_name, $genesis_txns);
            $batched = $agent->microledgers->batched();
            $batched->open([$ledger_name]);
            try {
                $ledgers = $batched->append($commit_txns);
                $ledger_from_batched = $ledgers[0];

                $ledger_from_local = $agent->microledgers->ledger($ledger_name);

                self::assertEquals(2, $ledger_from_batched->getUncommittedSize());
                self::assertEquals($ledger_from_batched->getUncommittedSize(), $ledger_from_local->getUncommittedSize());

                $batched->append($commit_txns);
                self::assertEquals(3, $ledger_from_local->getUncommittedSize());
            } finally {
                $batched->close();
            }
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_microledgers_in_same_context_space_2(): void
    {
        $agent = Conftest::agent4();
        $ledger_name = Conftest::ledger_name();
        $genesis_txns = [
            ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1', 'txnMetadata' => new stdClass()],
        ];
        $commit_txns = [
            ['reqId' => 2, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op2', 'txnMetadata' => new stdClass()],
        ];
        $agent->open();
        try {
            $agent->microledgers->create($ledger_name, $genesis_txns);
        } finally {
            $agent->close();
        }

        // Next open iteration refresh Agent context
        $agent->open();
        try {
            /** @var BatchedAPI $batched */
            $batched = $agent->microledgers->batched();
            $batched->open([$ledger_name]);
            try {
                $ledgers = $batched->append($commit_txns);
                $ledger_from_batched = $ledgers[0];

                $ledger_from_local = $agent->microledgers->ledger($ledger_name);

                self::assertEquals(2, $ledger_from_batched->getUncommittedSize());
                self::assertEquals($ledger_from_batched->getUncommittedSize(), $ledger_from_local->getUncommittedSize());

                $batched->append($commit_txns);
                self::assertEquals(3, $ledger_from_local->getUncommittedSize());
            } finally {
                $batched->close();
            }
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     */
    public function test_batched_ops_errors(): void
    {
        $agent = Conftest::agent4();
        $agent->open();
        try {
            $api = $agent->microledgers->batched();
            $exc = null;
            try {
                $api->open(['missing-ledger-name']);
            } catch (SiriusPromiseContextException $exception) {
                $exc = $exception;
            }
            self::assertNotNull($exc);
        } finally {
            $agent->close();
        }
    }
}
