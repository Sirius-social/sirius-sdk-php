<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Agent\Agent;
use Siruis\Tests\Helpers\Conftest;

class TestLocks extends TestCase
{
    public function test_same_account(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent1');
        $session1 = new Agent(
            $params['server_address'],
            $params['credentials'],
            $params['p2p'],
            5
        );
        $session2 = new Agent(
            $params['server_address'],
            $params['credentials'],
            $params['p2p'],
            5
        );
        $session1->open();
        $session2->open();
        try {
            $resources = [];
            for ($i = 0; $i < 3; $i++) {
                $resources[] = 'resources-' . uniqid('', true);
            }
            [$ok,] = $session1->acquire($resources, 5);
            try {
                self::assertTrue($ok);
                [$ok, $busy] = $session2->acquire($resources, 1);
                self::assertFalse($ok);
                sort($busy);
                sort($resources);
                self::assertEquals($busy, $resources);
            } finally {
                $session1->release();
            }
            // check session ok may lock after explicitly release
            [$ok, $busy] = $session2->acquire($resources, 1);
            self::assertTrue($ok);
            // Check after timeout
            $resources = [];
            for ($i = 0; $i < 100; $i++) {
                $resources[] = 'resources-' . uniqid('', true);
            }
            $timeout = 5.0;
            [$ok,] = $session1->acquire($resources, $timeout);
            self::assertTrue($ok);
            [$ok,] = $session2->acquire($resources, 1.0);
            self::assertFalse($ok);
            sleep($timeout + 1.0);
            [$ok,] = $session2->acquire($resources, 1.0);
            self::assertTrue($ok);
        } finally {
            $session1->close();
            $session2->close();
        }
    }

    public function test_lock_multiple_time(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent1');
        $session1 = new Agent(
            $params['server_address'],
            $params['credentials'],
            $params['p2p'],
            5
        );
        $session2 = new Agent(
            $params['server_address'],
            $params['credentials'],
            $params['p2p'],
            5
        );
        $session1->open();
        $session2->open();
        try {
            $resources1 = ['resources-'.uniqid('', true)];
            $timeout = 5.0;
            [$ok,] = $session1->acquire($resources1, $timeout);
            self::assertTrue($ok);

            $resources2 = ['resources-'.uniqid('', true)];
            [$ok,] = $session1->acquire($resources2, $timeout);
            self::assertTrue($ok);

            // session1 must unlock previously locked resources on new acquire call
            [$ok,] = $session2->acquire($resources1, $timeout);
            self::assertTrue($ok);
        } finally {
            $session1->close();
            $session2->close();
        }
    }

    public function test_different_accounts(): void
    {
        $test_suite = Conftest::test_suite();
        $params1 = $test_suite->get_agent_params('agent1');
        $params2 = $test_suite->get_agent_params('agent2');
        $agent1 = new Agent(
            $params1['server_address'],
            $params1['credentials'],
            $params1['p2p'],
            5
        );
        $agent2 = new Agent(
            $params2['server_address'],
            $params2['credentials'],
            $params2['p2p'],
            5
        );
        $agent1->open();
        $agent2->open();
        try {
            $same_resources = ['resource/'.uniqid('', true)];
            [$ok1,] = $agent1->acquire($same_resources, 10.0);
            [$ok2,] = $agent2->acquire($same_resources, 10.0);

            self::assertTrue($ok1);
            self::assertTrue($ok2);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
}
