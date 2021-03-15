<?php

namespace Siruis\Tests\Pairwise;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
use Siruis\Tests\Helpers\Conftest;

class TestPairwise extends TestCase
{
    public function test_create_and_store_my_did()
    {
        $agent = Conftest::agent1();
        $agent->open();
        try {
            $casmd = $agent->wallet->did->create_and_store_my_did();

        } finally {
            $agent->close();
        }
    }

    public function test_pairwise_list()
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $casmd1 = $agent1->wallet->did->create_and_store_my_did();
            $casmd2 = $agent2->wallet->did->create_and_store_my_did();
            $did1 = $casmd1[0];
            $did2 = $casmd2[0];
            $verkey1 = $casmd1[1];
            $verkey2 = $casmd2[1];
            $p = new Pairwise(
                new Me($did1, $verkey1),
                new Their($did2, 'Test-Pairwise', 'http://endpoint', $verkey2),
                ['test' => 'test-value']
            );
            $lst1 = $agent1->wallet->pairwise->list_pairwise();
            $agent1->pairwise_list->ensure_exists($p);
            $lst2 = $agent2->wallet->pairwise->list_pairwise();
            self::assertTrue(count($lst1) < count($lst2));

            $ok = $agent1->pairwise_list->is_exists($did2);
            self::assertTrue($ok);

            $loaded = $agent1->pairwise_list->load_for_verkey($verkey2);
            self::assertEquals($loaded->metadata, $p->metadata);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
}
