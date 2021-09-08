<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Consensus\Messages\CommitParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\CommitTransactionsMessage;
use Siruis\Agent\Consensus\Messages\InitRequestLedgerMessage;
use Siruis\Agent\Consensus\Messages\InitResponseLedgerMessage;
use Siruis\Agent\Consensus\Messages\MicroLedgerState;
use Siruis\Agent\Consensus\Messages\PostCommitParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\PostCommitTransactionsMessage;
use Siruis\Agent\Consensus\Messages\PreCommitParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\PreCommitTransactionsMessage;
use Siruis\Agent\Consensus\Messages\ProposeParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\ProposeTransactionsMessage;
use Siruis\Agent\Microledgers\AbstractMicroledger;
use Siruis\Agent\Microledgers\Transaction;
use Siruis\Tests\Helpers\Conftest;

class TestConsensusSimple extends TestCase
{
    public function test_init_ledger_messaging()
    {
        $A = Conftest::A();
        $B = Conftest::B();
        $ledger_name = Conftest::ledger_name();
        $A->open();
        $B->open();
        try {
            $A2B = Conftest::get_pairwise($A, $B);
            $B2A = Conftest::get_pairwise($B, $A);
            $A2B->me->did = 'did:peer:'.$A2B->me->did;
            $B2A->me->did = 'did:peer:'.$B2A->me->did;
            $payload = ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'];
            $genesis_txns = [
                new Transaction($payload)
            ];
            $request = new InitRequestLedgerMessage(
                [],
                null,
                $ledger_name,
                $genesis_txns,
                'xxx',
                [$A2B->me->did, $B2A->me->did]
            );

            $request->add_signature($A->wallet->crypto, $A2B->me);
            $request->add_signature($B->wallet->crypto, $B2A->me);

            $signatures = $request->getSignatures();

            self::assertCount(2, $request->getSignatures());

            $request->check_signatures($A->wallet->crypto, $A2B->me->did);
            $request->check_signatures($A->wallet->crypto, $B2A->me->did);
            $request->check_signatures($A->wallet->crypto);
            $request->check_signatures($B->wallet->crypto, $A2B->me->did);
            $request->check_signatures($B->wallet->crypto, $B2A->me->did);
            $request->check_signatures($B->wallet->crypto);

            $response = new InitResponseLedgerMessage([]);
            $response->assign_from($request);

            $payload1 = $request->payload;
            $payload2 = $response->payload;
            self::assertNotEquals($payload1, $payload2);

            unset($payload1['@id']);
            unset($payload1['@type']);
            unset($payload2['@id']);
            unset($payload2['@type']);
            self::assertEquals($payload1, $payload2);
        } finally {
            $A->close();
            $B->close();
        }
    }

    public function test_transaction_messaging()
    {
        $A = Conftest::A();
        $B = Conftest::B();
        $ledger_name = Conftest::ledger_name();
        $A->open();
        $B->open();
        try {
            $a2b = Conftest::get_pairwise($A, $B);
            $b2a = Conftest::get_pairwise($B, $A);
            $a2b->me->did = 'did:peer:'.$a2b->me->did;
            $b2a->me->did = 'did:peer:'.$b2a->me->did;
            $payload = ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'];
            $genesis_txns = [
                new Transaction($payload)
            ];
            list($ledger_for_a, $txns) = $A->microledgers->create($ledger_name, $genesis_txns);
            list($ledger_for_b, $txns) = $B->microledgers->create($ledger_name, $genesis_txns);

            $txn1 = ["reqId" => 2, "identifier" => "5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC", "op" => "op2"];
            $txn2 = ["reqId" => 3, "identifier" => "5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC", "op" => "op3"];
            $new_transactions = [new Transaction($txn1), new Transaction($txn2)];
            list($pos1, $pos2, $new_txns) = $ledger_for_a->append($new_transactions);
            // A -> B
            $state = new MicroLedgerState(
                [
                    'name' => $ledger_for_a->getName(),
                    'seq_no' => $ledger_for_a->getSeqNo(),
                    'size' => $ledger_for_a->getSize(),
                    'uncommitted_size' => $ledger_for_a->getUncommittedSize(),
                    'root_hash' => $ledger_for_a->getRootHash(),
                    'uncommitted_root_hash' => $ledger_for_a->getUncommittedRootHash()
                ]
            );
            $x = MicroLedgerState::from_ledger($ledger_for_a);
            self::assertEquals($state, $x);
            self::assertEquals($state->getHash(), $x->getHash());
            $propose = new ProposeTransactionsMessage([], $new_txns, $state);
            $propose->validate();
            // B -> A
            $ledger_for_b->append($propose->transactions);
            $pre_commit = new PreCommitTransactionsMessage(
                [],
                null,
                new MicroLedgerState(
                    [
                        'name' => $ledger_for_b->getName(),
                        'seq_no' => $ledger_for_b->getSeqNo(),
                        'size' => $ledger_for_b->getSize(),
                        'uncommitted_size' => $ledger_for_b->getUncommittedSize(),
                        'root_hash' => $ledger_for_b->getRootHash(),
                        'uncommitted_root_hash' => $ledger_for_b->getUncommittedRootHash()
                    ]
                )
            );
            $pre_commit->sign_state($B->wallet->crypto, $b2a->me);
            $pre_commit->validate();
            list($ok, $loaded_state_hash) = $pre_commit->verify_state($A->wallet->crypto, $a2b->their->verkey);
            self::assertTrue($ok);
            self::assertEquals($state->getHash(), $loaded_state_hash);
            // A -> B
            $commit = new CommitTransactionsMessage([]);
            $commit->add_pre_commit($a2b->their->did, $pre_commit);
            $commit->validate();
            $states = $commit->verify_pre_commits($A->wallet->crypto, $state);
            self::assertStringContainsString($a2b->their->did, $state);
            self::assertStringContainsString($a2b->their->verkey, $state);
            // B -> A (post-commit)
            $post_commit = new PostCommitTransactionsMessage([]);
            $post_commit->add_commit_sign($B->wallet->crypto, $commit, $b2a->me);
            $post_commit->validate();
            $ok = $post_commit->verify_commits($A->wallet->crypto, $commit, [$a2b->their->verkey]);
            self::assertTrue($ok);
        } finally {
            $A->close();
            $B->close();
        }
    }

    public function test_parallel_transactions_messaging()
    {
        $A = Conftest::A();
        $B = Conftest::B();
        $ledger_names = Conftest::ledger_names();
        $A->open();
        $B->open();
        try {
            $txn_time = (string)date('Y-m-d h:i:s', time());
            $a2b = Conftest::get_pairwise($A, $B);
            $b2a = Conftest::get_pairwise($B, $A);
            $a2b->me->did = 'did:peer:'.$a2b->me->did;
            $b2a->me->did = 'did:peer:'.$b2a->me->did;
            $payload = ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'];
            $genesis_txns = [
                new Transaction($payload)
            ];
            $ledgers_for_a = [];
            $ledgers_for_b = [];
            foreach ($ledger_names as $n) {
                list($l_for_a, $_) = $A->microledgers->create($n, $genesis_txns);
                list($l_for_b, $_) = $B->microledgers->create($n, $genesis_txns);
                array_push($ledgers_for_a, $l_for_a);
                array_push($ledgers_for_b, $l_for_b);
            }

            $txn1 = ["reqId" => 2, "identifier" => "5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC", "op" => "op2"];
            $txn2 = ["reqId" => 3, "identifier" => "5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC", "op" => "op3"];
            $new_transactions = [new Transaction($txn1), new Transaction($txn2)];
            foreach ($new_transactions as $txn) {
                $txn->setTime($txn_time);
            }
            // A -> B
            $states_for_a = [];
            foreach ($ledgers_for_a as $ledger_for_a) {
                list($pos1, $pos2, $new_txns) = $ledger_for_a->append($new_transactions);
                $state = MicroLedgerState::from_ledger($ledger_for_a);
                array_push($states_for_a, $state);
            }
            $propose = new ProposeParallelTransactionsMessage([], $new_transactions, $states_for_a);
            $propose->validate();
            // B -> A
            $states_for_b = [];
            foreach ($propose->getLedgers() as $ledger_name) {
                $ledger_for_b = $B->microledgers->ledger($ledger_name);
                list($pos1, $pos2, $new_txns) = $ledger_for_b->append($propose->getTransactions());
                array_push($states_for_b, MicroLedgerState::from_ledger($ledger_for_b));
            }
            $pre_commit = new PreCommitParallelTransactionsMessage(
                [],
                $new_transactions,
                $states_for_b
            );
            $pre_commit->sign_states($B->wallet->crypto, $b2a->me);
            $pre_commit->validate();
            list($ok, $state_hash_for_b) = $pre_commit->verify_state($A->wallet->crypto, $a2b->their->verkey);
//            self::assertTrue($ok);
            self::assertEquals($propose->getHash(), $state_hash_for_b);
            // A -> B
            $commit = new CommitParallelTransactionsMessage([]);
            $commit->add_pre_commit($a2b->their->did, $pre_commit);
            $commit->validate();
            $states = $commit->verify_pre_commits($A->wallet->crypto, $propose->getHash());
            self::assertStringContainsString($a2b->their->did, json_encode($states));
            self::assertStringContainsString($a2b->their->verkey, json_encode($states));
            // B -> A (post-commit)
            $post_commit = new PostCommitParallelTransactionsMessage([]);
            $post_commit->add_commit_sign($B->wallet->crypto, $commit, $b2a->me);
            $post_commit->validate();
            $ok = $post_commit->verify_commits($A->wallet->crypto, $commit, [$a2b->their->verkey]);
            self::assertTrue($ok);
        } finally {
            $A->close();
            $B->close();
        }
    }

    public function test_parallel_batching_api_messaging()
    {
        $txn_time = (string)date('Y-m-d h:i:s', time());
        $A = Conftest::A();
        $B = Conftest::B();
        $ledger_names = Conftest::ledger_names();
        $A->open();
        $B->open();
        try {
            $a2b = Conftest::get_pairwise($A, $B);
            $b2a = Conftest::get_pairwise($B, $A);
            $a2b->me->did = 'did:peer:'.$a2b->me->did;
            $b2a->me->did = 'did:peer:'.$b2a->me->did;
            $payload = ['reqId' => 1, 'identifier' => '5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC', 'op' => 'op1'];
            $genesis_txns = [
                new Transaction($payload)
            ];
            $ledgers_for_a = [];
            $ledgers_for_b = [];
            foreach ($ledger_names as $n) {
                list($l_for_a, $_) = $A->microledgers->create($n, $genesis_txns);
                list($l_for_b, $_) = $B->microledgers->create($n, $genesis_txns);
                array_push($ledgers_for_a, $l_for_a);
                array_push($ledgers_for_b, $l_for_b);
            }
            $txn1 = ["reqId" => 2, "identifier" => "5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC", "op" => "op2"];
            $txn2 = ["reqId" => 3, "identifier" => "5rArie7XKukPCaEwq5XGQJnM9Fc5aZE3M9HAPVfMU2xC", "op" => "op3"];
            $new_transactions = [new Transaction($txn1), new Transaction($txn2)];
            foreach ($new_transactions as $txn) {
                $txn->setTime($txn_time);
            }

            $batching_api_for_a = $A->microledgers->batched();
            $batching_api_for_b = $B->microledgers->batched();
            $batching_api_for_a->open();
            $batching_api_for_b->open();
            try {
                // A -> B
                $batching_api_for_a->append($new_transactions);
                $propose_ml_states = [];
                foreach ($ledgers_for_a as $item) {
                    array_push($propose_ml_states, MicroLedgerState::from_ledger($item));
                }
                $propose = new ProposeParallelTransactionsMessage([], $new_transactions, $propose_ml_states);
                $propose->validate();
                // B -> A
                $batching_api_for_b->append($propose->getTransactions());
                $states_for_b = [];
                foreach ($ledgers_for_b as $item) {
                    array_push($states_for_b, MicroLedgerState::from_ledger($item));
                }
                $pre_commit = new PreCommitParallelTransactionsMessage([], null, $states_for_b);
                $pre_commit->sign_states($B->wallet->crypto, $b2a->me);
                $pre_commit->validate();
                list($ok, $state_hash_for_b) = $pre_commit->verify_state($A->wallet->crypto, $a2b->their->verkey);
                self::assertTrue($ok);
                self::assertEquals($propose->getHash(), $state_hash_for_b);
                // A -> B
                $commit = new CommitParallelTransactionsMessage([]);
                $commit->add_pre_commit($a2b->their->did, $pre_commit);
                $commit->validate();
                $states = $commit->verify_pre_commits($A->wallet->crypto, $propose->getHash());
                self::assertStringContainsString($a2b->their->did, json_encode($states));
                self::assertStringContainsString($a2b->their->verkey, json_encode($states));
                // B -> A (post-commit)
                $post_commit = new PostCommitParallelTransactionsMessage([]);
                $post_commit->add_commit_sign($B->wallet->crypto, $commit, $b2a->me);
                $post_commit->validate();
                $ok = $post_commit->verify_commits($A->wallet->crypto, $commit, [$a2b->their->verkey]);
                self::assertTrue($ok);
            } finally {
                $batching_api_for_a->close();
                $batching_api_for_b->close();
            }
        } finally {
            $A->close();
            $B->close();
        }
    }
}
