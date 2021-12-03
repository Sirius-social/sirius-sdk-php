<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Agent\Agent;
use Siruis\Agent\Coprotocols\PairwiseCoProtocolTransport;
use Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport;
use Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
use Siruis\Agent\Pairwise\TheirEndpoint;
use Siruis\Hub\Coprotocols\CoProtocolP2P;
use Siruis\Hub\Coprotocols\CoProtocolP2PAnon;
use Siruis\Hub\Coprotocols\CoProtocolThreadedP2P;
use Siruis\Messaging\Message;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\Threads\Coprotocols\DelayedAborter;
use Siruis\Tests\Threads\Coprotocols\FirstTask;
use Siruis\Tests\Threads\Coprotocols\FirstTaskOnHub;
use Siruis\Tests\Threads\Coprotocols\InfiniteReader;
use Siruis\Tests\Threads\Coprotocols\SecondTask;
use Siruis\Tests\Threads\Coprotocols\SecondTaskOnHub;
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

    protected function check_msg_log(): void
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

    public function test__their_endpoint_protocol(): void
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
            $agent1_protocol = $agent1->spawnTheirEndpoint($entity1['verkey'], $their1);
            self::assertInstanceOf(TheirEndpointCoProtocolTransport::class, $agent1_protocol);
            $their2 = new TheirEndpoint($agent1_endpoints, $entity1['verkey']);
            $agent2_protocol = $agent2->spawnTheirEndpoint($entity2['verkey'], $their2);
            self::assertInstanceOf(TheirEndpointCoProtocolTransport::class, $agent2_protocol);
            $agent1_protocol->start(['test-protocol']);
            $agent2_protocol->start(['test-protocol']);
            try {
                self::$MSG_LOG = [];
                Threads::run_threads([new FirstTask($agent1_protocol), new SecondTask($agent2_protocol)]);
                $this->check_msg_log();
            } finally {
                $agent1_protocol->stop();
                $agent2_protocol->stop();
            }
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    public function test__their_endpoint_protocol_on_hub(): void
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
            $agent1_endpoint = Conftest::get_endpoints($agent1->endpoints)[0]->address;
            $agent2_endpoint = Conftest::get_endpoints($agent2->endpoints)[0]->address;
        } finally {
            $agent1->close();
            $agent2->close();
        }

        // FIRE!!!
        $their1 = new TheirEndpoint($agent2_endpoint, $entity2['verkey']);
        $their2 = new TheirEndpoint($agent1_endpoint, $entity1['verkey']);
        $co1 = new CoProtocolP2PAnon($entity1['verkey'], $their1, ['test_protocol']);
        $co2 = new CoProtocolP2PAnon($entity2['verkey'], $their2, ['test_protocol']);
        self::$MSG_LOG = [];
        Threads::run_threads([
            new FirstTaskOnHub($co1, $agent2->server_address, $agent1->credentials, $agent1->p2p),
            new SecondTaskOnHub($co2, $agent2->server_address, $agent2->credentials, $agent2->p2p)
        ]);
        $this->check_msg_log();
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test__pairwise_protocol(): void
    {
        $test_suite = Conftest::test_suite();
        $agent1_params = $test_suite->get_agent_params('agent1');
        $agent2_params = $test_suite->get_agent_params('agent2');
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
            $agent1_endpoint = Conftest::get_endpoints($agent1->endpoints)[0]->address;
            $agent2_endpoint = Conftest::get_endpoints($agent2->endpoints)[0]->address;
            // Init pairwise list #1
            [$did1, $verkey1] = $agent1->wallet->did->create_and_store_my_did();
            [$did2, $verkey2] = $agent2->wallet->did->create_and_store_my_did();
            $agent1->wallet->did->store_their_did($did2, $verkey2);
            $agent1->wallet->pairwise->create_pairwise($did2, $did1);
            $agent2->wallet->did->store_their_did($did1, $verkey1);
            $agent2->wallet->pairwise->create_pairwise($did1, $verkey1);
            // Init pairwise list #2
            $pairwise1 = new Pairwise(
                new Me($did1, $verkey1),
                new Their($did2, 'Label-2', $agent2_endpoint, $verkey2)
            );
            $pairwise2 = new Pairwise(
                new Me($did2, $verkey2),
                new Their($did1, 'Label-1', $agent1_endpoint, $verkey1)
            );

            $agent1_protocol = $agent1->spawnPairwise($pairwise1);
            $agent2_protocol = $agent2->spawnPairwise($pairwise2);
            self::assertInstanceOf(PairwiseCoProtocolTransport::class, $agent1_protocol);
            self::assertInstanceOf(PairwiseCoProtocolTransport::class, $agent2_protocol);

            $agent1_protocol->start(['test_protocol']);
            $agent2_protocol->start(['test_protocol']);

            try {
                self::$MSG_LOG = [];
                Threads::run_threads([new FirstTask($agent1_protocol), new SecondTask($agent2_protocol)]);
                $this->check_msg_log();
            } finally {
                $agent1_protocol->stop();
                $agent2_protocol->stop();
            }
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test__pairwise_protocol_on_hub(): void
    {
        $test_suite = Conftest::test_suite();
        $agent1_params = $test_suite->get_agent_params('agent1');
        $agent2_params = $test_suite->get_agent_params('agent2');
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
            $agent1_endpoint = Conftest::get_endpoints($agent1->endpoints)[0]->address;
            $agent2_endpoint = Conftest::get_endpoints($agent2->endpoints)[0]->address;
            // Init pairwise list #1
            [$did1, $verkey1] = $agent1->wallet->did->create_and_store_my_did();
            [$did2, $verkey2] = $agent2->wallet->did->create_and_store_my_did();
            $agent1->wallet->did->store_their_did($did2, $verkey2);
            $agent1->wallet->pairwise->create_pairwise($did2, $did1);
            $agent2->wallet->did->store_their_did($did1, $verkey1);
            $agent2->wallet->pairwise->create_pairwise($did1, $verkey1);
            // Init pairwise list #2
            $pairwise1 = new Pairwise(
                new Me($did1, $verkey1),
                new Their($did2, 'Label-2', $agent2_endpoint, $verkey2)
            );
            $pairwise2 = new Pairwise(
                new Me($did2, $verkey2),
                new Their($did1, 'Label-1', $agent1_endpoint, $verkey1)
            );
        } finally {
            $agent1->close();
            $agent2->close();
        }

        $co1 = new CoProtocolP2P($pairwise1, ['test_protocol']);
        $co2 = new CoProtocolP2P($pairwise2, ['test_protocol']);
        self::$MSG_LOG = [];
        Threads::run_threads([
            new FirstTaskOnHub($co1, $agent2->server_address, $agent1->credentials, $agent1->p2p),
            new SecondTaskOnHub($co2, $agent2->server_address, $agent2->credentials, $agent2->p2p)
        ]);
        $this->check_msg_log();
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test__threadbased_protocol(): void
    {
        $test_suite = Conftest::test_suite();
        $agent1_params = $test_suite->get_agent_params('agent1');
        $agent2_params = $test_suite->get_agent_params('agent2');
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
            $agent1_endpoint = Conftest::get_endpoints($agent1->endpoints)[0]->address;
            $agent2_endpoint = Conftest::get_endpoints($agent2->endpoints)[0]->address;
            // Init pairwise list #1
            [$did1, $verkey1] = $agent1->wallet->did->create_and_store_my_did();
            [$did2, $verkey2] = $agent2->wallet->did->create_and_store_my_did();
            $agent1->wallet->did->store_their_did($did2, $verkey2);
            $agent1->wallet->pairwise->create_pairwise($did2, $did1);
            $agent2->wallet->did->store_their_did($did1, $verkey1);
            $agent2->wallet->pairwise->create_pairwise($did1, $verkey1);
            // Init pairwise list #2
            $pairwise1 = new Pairwise(
                new Me($did1, $verkey1),
                new Their($did2, 'Label-2', $agent2_endpoint, $verkey2)
            );
            $pairwise2 = new Pairwise(
                new Me($did2, $verkey2),
                new Their($did1, 'Label-1', $agent1_endpoint, $verkey1)
            );
        } finally {
            $agent1->close();
            $agent2->close();
        }

        $thread_id = uniqid('', true);
        $co1 = new CoProtocolThreadedP2P($thread_id, $pairwise1);
        $co2 = new CoProtocolThreadedP2P($thread_id, $pairwise2);
        self::$MSG_LOG = [];
        Threads::run_threads([
            new FirstTaskOnHub($co1, $agent2->server_address, $agent1->credentials, $agent1->p2p),
            new SecondTaskOnHub($co2, $agent2->server_address, $agent2->credentials, $agent2->p2p)
        ]);
        $this->check_msg_log();
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test__protocols_intersections(): void
    {
        $test_suite = Conftest::test_suite();
        $agent1_params = $test_suite->get_agent_params('agent1');
        $agent2_params = $test_suite->get_agent_params('agent2');
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
            $agent1_endpoint = Conftest::get_endpoints($agent1->endpoints)[0]->address;
            $agent2_endpoint = Conftest::get_endpoints($agent2->endpoints)[0]->address;
            // Init pairwise list #1
            [$did1, $verkey1] = $agent1->wallet->did->create_and_store_my_did();
            [$did2, $verkey2] = $agent2->wallet->did->create_and_store_my_did();
            $agent1->wallet->did->store_their_did($did2, $verkey2);
            $agent1->wallet->pairwise->create_pairwise($did2, $did1);
            $agent2->wallet->did->store_their_did($did1, $verkey1);
            $agent2->wallet->pairwise->create_pairwise($did1, $verkey1);
            // Init pairwise list #2
            $pairwise1 = new Pairwise(
                new Me($did1, $verkey1),
                new Their($did2, 'Label-2', $agent2_endpoint, $verkey2)
            );
            $pairwise2 = new Pairwise(
                new Me($did2, $verkey2),
                new Their($did1, 'Label-1', $agent1_endpoint, $verkey1)
            );

            $thread_id = uniqid('', true);
            $agent1_protocol_threaded = $agent1->spawnThidPairwise($thread_id, $pairwise1);
            $agent2_protocol_threaded = $agent2->spawnThidPairwise($thread_id, $pairwise2);
            self::assertInstanceOf(ThreadBasedCoProtocolTransport::class, $agent1_protocol_threaded);
            self::assertInstanceOf(ThreadBasedCoProtocolTransport::class, $agent2_protocol_threaded);
            $agent1_protocol_pairwise = $agent1->spawnPairwise($pairwise1);
            $agent2_protocol_pairwise = $agent2->spawnPairwise($pairwise2);
            self::assertInstanceOf(PairwiseCoProtocolTransport::class, $agent1_protocol_pairwise);
            self::assertInstanceOf(PairwiseCoProtocolTransport::class, $agent2_protocol_pairwise);

            $agent1_protocol_threaded->start(['test_protocol']);
            $agent2_protocol_threaded->start(['test_protocol']);
            $agent1_protocol_pairwise->start(['test_protocol']);
            $agent2_protocol_pairwise->start(['test_protocol']);
            try {
                self::$MSG_LOG = [];
                Threads::run_threads([
                    new FirstTask($agent1_protocol_threaded), new SecondTask($agent2_protocol_threaded),
                    new FirstTask($agent1_protocol_pairwise), new SecondTask($agent2_protocol_pairwise)
                ]);
                // collect messages
                $threaded_sequence = [];
                $not_threaded_sequence = [];
                foreach (self::$MSG_LOG as $message) {
                    if (array_key_exists('~thread', $message)) {
                        $threaded_sequence[] = $message;
                    } else {
                        $not_threaded_sequence[] = $message;
                    }
                }
                self::$MSG_LOG = [];
                array_merge(self::$MSG_LOG, $threaded_sequence);
                $this->check_msg_log();
                foreach ($threaded_sequence as $message) {
                    self::assertEquals($thread_id, $message['~thread']['thid']);
                }
                // non-thread messages
                self::$MSG_LOG = [];
                array_merge(self::$MSG_LOG, $not_threaded_sequence);
                $this->check_msg_log();
            } finally {
                $agent1_protocol_threaded->stop();
                $agent2_protocol_threaded->stop();
                $agent1_protocol_pairwise->stop();
                $agent2_protocol_pairwise->stop();
            }
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    public function test_coprotocol_abort(): void
    {
        $test_suite = Conftest::test_suite();
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1_params = $test_suite->get_agent_params('agent1');
        $agent1->open();
        $agent2->open();
        try {
            $pw1 = Conftest::get_pairwise($agent1, $agent2);
        } finally {
            $agent1->close();
            $agent2->close();
        }
        $co = new CoProtocolThreadedP2P(uniqid('', true), $pw1);
        Threads::run_threads([
            new InfiniteReader($co, $agent1->server_address, $agent1->credentials, $agent1->p2p),
            new DelayedAborter($co)
        ]);
    }
}
