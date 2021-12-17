<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\Helpers\Conftest;

class TestHub extends TestCase
{
    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_sane(): void
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
            $new_endpoints1[] = $e->address;
        }
        $new_endpoints2 = [];
        foreach ($endpoints2 as $e) {
            $new_endpoints2[] = $e->address;
        }
        $new_my_did_list1 = [];
        foreach ($my_did_list1 as $d) {
            $new_my_did_list1[] = $d['did'];
        }
        $new_my_did_list2 = [];
        foreach ($my_did_list2 as $d) {
            $new_my_did_list2[] = $d['did'];
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

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     */
    public function test_aborting(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent1');
        Hub::alloc_context($params['server_address'], $params['credentials'], $params['p2p']);
        try {
            $hub = Hub::current_hub();
        } finally {
            Hub::free_context();
        }

        $agent1 = $hub->get_agent_connection_lazy();
        $ok1 = $agent1->ping();
        self::assertTrue($ok1);

        $hub->abort();

        $agent2 = $hub->get_agent_connection_lazy();
        $ok2 = $agent2->ping();
        self::assertTrue($ok2);
        self::assertNotEquals(spl_object_id($agent2), spl_object_id($agent1));
        self::assertNotNull($agent1);
        self::assertNotNull($agent2);
    }
}
