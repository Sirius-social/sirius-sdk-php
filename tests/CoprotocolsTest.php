<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Agent\Agent;
use Siruis\Agent\Coprotocols\AbstractCoProtocolTransport;
use Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport;
use Siruis\Agent\Pairwise\TheirEndpoint;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Coprotocols\AbstractCoProtocol;
use Siruis\Hub\Core\Hub;
use Siruis\Messaging\Message;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\Helpers\FirstTask;
use Siruis\Tests\Helpers\SecondTask;
use Siruis\Tests\Helpers\Threads;

class CoprotocolsTest extends TestCase
{
    public static $TEST_MSG_TYPES = [
        "did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test_protocol/1.0/request-1",
        "did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test_protocol/1.0/response-1",
        "did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test_protocol/1.0/request-2",
        "did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test_protocol/1.0/response-2"
    ];

    /**
     * @var Message[]
     */
    public static $MSG_LOG = [];

    protected function check_msg_log()
    {
        self::assertCount(count(self::$TEST_MSG_TYPES), self::$MSG_LOG);
        foreach (self::$TEST_MSG_TYPES as $i => $item) {
            self::assertEquals(self::$TEST_MSG_TYPES[$i], self::$MSG_LOG[$i]->type);
        }
        self::assertEquals('Request1', self::$MSG_LOG[0]['content']);
        self::assertEquals('Response1', self::$MSG_LOG[1]['content']);
        self::assertEquals('Request2', self::$MSG_LOG[2]['content']);
        self::assertEquals('End', self::$MSG_LOG[3]['content']);
    }

    /** @test */
    public function test__their_endpoint_protocol()
    {
        $test_suite = Conftest::test_suite();
        $agent1_params = $test_suite->get_agent_params('agent1');
        $agent2_params = $test_suite->get_agent_params('agent2');
        $entity1 = array_values($agent1_params['entities'])[0];
        $entity2 = array_values($agent2_params['entities'])[0];
        $agent1 = new Agent(
            $agent1_params['server_address'],
            $agent1_params['credentials'],
            $agent1_params['p2p'],
            5
        );
        $agent2 = new Agent(
            $agent2_params['server_address'],
            $agent2_params['credentials'],
            $agent2_params['p2p'],
            5
        );
        $agent1->open();
        $agent2->open();
        try {
            // Get endpoints
            $agent1_endpoints = Conftest::get_endpoints($agent1->endpoints)[0]->address;
            $agent2_endpoints = Conftest::get_endpoints($agent2->endpoints)[0]->address;
            // Make protocol instances
            $their1 = new TheirEndpoint($agent2_endpoints, $entity2['verkey']);
            $agnet1_protocol = $agent1->spawnTheirEndpoint($entity1['verkey'], $their1);
            self::assertInstanceOf(TheirEndpointCoProtocolTransport::class, $agnet1_protocol);
            $their2 = new TheirEndpoint($agent1_endpoints, $entity1['verkey']);
            $agnet2_protocol = $agent1->spawnTheirEndpoint($entity2['verkey'], $their2);
            self::assertInstanceOf(TheirEndpointCoProtocolTransport::class, $agnet2_protocol);
            $agnet1_protocol->start(['test-protocol']);
            $agnet2_protocol->start(['test-protocol']);
            try {
                self::$MSG_LOG = [];
                Threads::run_threads([new FirstTask($agnet1_protocol), new SecondTask($agnet2_protocol)]);
                $this->check_msg_log();
            } finally {
                $agnet1_protocol->stop();
                $agnet2_protocol->stop();
            }
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
}
