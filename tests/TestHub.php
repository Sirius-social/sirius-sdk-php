<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\Helpers\Conftest;
use Swoole\Lock;

class TestHub extends TestCase
{
    public function testswool()
    {
        $lock = new Lock(SWOOLE_MUTEX);
        echo "[Master] Create lock\n";
        $lock->lock();
        if (pcntl_fork() > 0)
        {
            sleep(1);
            $lock->unlock();
        }
        else
        {
            echo "[Child] Wait Lock\n";
            $lock->lock();
            echo "[Child] Get Lock\n";
            $lock->unlock();
            exit("[Child] exit\n");
        }
        echo "[Master]release lock\n";
        unset($lock);
        sleep(1);
        echo "[Master]exit\n";
    }
    /** @test */
    public function test_sane()
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent1');

        Hub::alloc_context($params['server_address'], $params['credentials'], $params['p2p']);
        try {
            $inst1 = Hub::current_hub();
            $inst2 = Hub::current_hub();
        } finally {
            Hub::free_context();
        }
        self::assertEquals(spl_object_id($inst1), spl_object_id($inst2));
        $params1 = $test_suite->get_agent_params('agent1');
        $params2 = $test_suite->get_agent_params('agent2');

        Hub::alloc_context($params1['server_address'], $params1['credentials'], $params1['p2p']);
        $ping1 = Init::ping();
        $endpoints1 = Init::endpoints();
        $my_did_list1 = Init::DID()->list_my_dids_with_meta();

        Hub::alloc_context($params2['server_address'], $params2['credentials'], $params2['p2p']);
        $ping2 = Init::ping();
        $endpoints2 = Init::endpoints();
        $my_did_list2 = Init::DID()->list_my_dids_with_meta();

        $new_endpoints1 = [];
        foreach ($endpoints1 as $e) {
            array_push($new_endpoints1, $e->address);
        }
        $new_endpoints2 = [];
        foreach ($endpoints2 as $e) {
            array_push($new_endpoints2, $e->address);
        }
        $new_my_did_list1 = [];
        foreach ($my_did_list1 as $d) {
            array_push($new_my_did_list1, $d['did']);
        }
        $new_my_did_list2 = [];
        foreach ($my_did_list2 as $d) {
            array_push($new_my_did_list2, $d['did']);
        }
        self::assertTrue($ping1);
        self::assertTrue($ping2);
        sort($new_endpoints1);
        sort($new_endpoints2);
        sort($new_my_did_list1);
        sort($new_my_did_list2);
        self::assertNotEquals($new_endpoints2, $new_endpoints1);
        self::assertNotEquals($new_my_did_list2, $new_my_did_list1);
    }
}
