<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Agent\Agent;
use Siruis\Tests\Helpers\Conftest;

class TestLocks extends TestCase
{
    public function test_same_account()
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent1');
        $session1 = new Agent(
            $params['server_address'],
            $params['credentials'],
            $params['p2p'],
            5
        );
//        $params = $test_suite->get_agent_params('agent2');
        $session2 = new Agent(
            $params['server_address'],
            $params['credentials'],
            $params['p2p'],
            5
        );
        $session1->open();
        $session2->open();
        $is_open1 = $session1->isOpen();
        $is_open2 = $session2->isOpen();
        try {
            $resources = [];
            for ($i = 0; $i < 100; $i++) {
                array_push($resources, 'resources-'.uniqid());
            }
            list($ok, $busy) = $session1->acquire($resources, 5);
            try {
                self::assertTrue($ok);
                list($ok, $busy) = $session2->acquire($resources, 1);
                self::assertFalse($ok);
                self::assertEquals(array_unique($busy), array_unique($resources));
            } finally {
                $session1->release();
            }
            // check session ok may lock after explicitly release
            list($ok, $busy) = $session2->acquire($resources, 1);
            self::assertTrue($ok);
            // Check after timeout
            $resources = [];
            for ($i = 0; $i < 100; $i++) {
                array_push($resources, 'resources-'.uniqid());
            }
            $timeout = 5.0;
            list($ok, $_) = $session1->acquire($resources, $timeout);
            self::assertTrue($ok);
            list($ok, $_) = $session2->acquire($resources, 1.0);
            self::assertFalse($ok);
            sleep($timeout + 1.0);
            list($ok, $_) = $session2->acquire($resources, 1.0);
            self::assertTrue($ok);
        } finally {
            $session1->close();
            $session2->close();
        }
    }
}
