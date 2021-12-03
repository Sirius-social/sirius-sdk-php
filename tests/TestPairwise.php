<?php


namespace Siruis\Tests;


use PHPUnit\Framework\TestCase;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
use Siruis\Tests\Helpers\Conftest;

class TestPairwise extends TestCase
{
    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_create_and_store_my_did_sane(): void
    {
        $agent = Conftest::agent1();
        $agent->open();
        try {
            [$did, $verkey] = $agent->wallet->did->create_and_store_my_did();
            self::assertNotNull($did);
            self::assertNotNull($verkey);
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
    public function test_pairwise_list(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            [$did1, $verkey1] = $agent1->wallet->did->create_and_store_my_did();
            [$did2, $verkey2] = $agent2->wallet->did->create_and_store_my_did();
            $pairwise = new Pairwise(
                new Me(
                    $did1, $verkey1
                ),
                new Their(
                    $did2, 'Test-Pairwise', 'http://endpoint', $verkey2
                ),
                ['test' => 'test-value']
            );

            $lst1 = $agent1->wallet->pairwise->list_pairwise();
            $agent1->pairwise_list->ensure_exists($pairwise);
            $lst2 = $agent1->wallet->pairwise->list_pairwise();
            self::assertGreaterThan(count($lst1), count($lst2));

            $ok = $agent1->pairwise_list->is_exists($did2);
            self::assertTrue($ok);

            $loaded = $agent1->pairwise_list->load_for_verkey($verkey2);
            self::assertEquals($loaded->metadata, $pairwise->metadata);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_create_pairwise(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            [$my_did, $my_vk] = $agent1->wallet->did->create_and_store_my_did();
            [$their_did, $their_vk] = $agent2->wallet->did->create_and_store_my_did();
            $pairwise = new Pairwise(
                new Me(
                    $my_did, $my_vk
                ),
                new Their(
                    $their_did, 'Test-Pairwise', 'http://endpoint', $their_vk
                ),
                ['test' => 'test-value']
            );
            $ok = $agent1->pairwise_list->is_exists($their_did);
            self::assertFalse($ok);
            $agent1->pairwise_list->create($pairwise);
            $ok = $agent1->pairwise_list->is_exists($their_did);
            self::assertTrue($ok);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
}