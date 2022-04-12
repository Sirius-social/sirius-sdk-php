<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Agent\Agent;
use Siruis\Encryption\Encryption;
use Siruis\Messaging\Message;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\Helpers\TrustPingMessageUnderTest;

class AgentTest extends TestCase
{
    /**
     * @return void
     */
    public function test__all_agents_ping(): void
    {
        $test_suite = Conftest::test_suite();
        foreach (['agent1', 'agent2', 'agent3', 'agent4'] as $name) {
            $params = $test_suite->get_agent_params($name);
            $agent = new Agent(
                $params['server_address'],
                $params['credentials'],
                $params['p2p'],
                5
            );
            $agent->open();
            try {
                $success = $agent->ping();
                self::assertTrue($success, "agent $name is not ping-able");
            } finally {
                $agent->close();
            }
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_agents_wallet(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent1');
        $agent = new Agent(
            $params['server_address'],
            $params['credentials'],
            $params['p2p'],
            5
        );
        $agent->open();
        try {
            // Check wallet calls is ok.
            [$did, $verkey] = $agent->wallet->did->create_and_store_my_did();
            self::assertNotNull($did);
            self::assertNotNull($verkey);
            // Check reopen is ok.
            $agent->reopen();
        } finally {
            $agent->close();
        }
    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_agents_communications(): void
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
            $agent2_endpoint = Conftest::get_endpoints($agent2->endpoints)[0]->address;
            $agent2_listener = $agent2->subscribe();
            // Exchange pairwise
            $agent1->wallet->did->store_their_did($entity2['did'], $entity2['verkey']);
            if (!$agent1->wallet->pairwise->is_pairwise_exists($entity2['did'])) {
                printf('#1');
                $agent1->wallet->pairwise->create_pairwise(
                    $entity2['did'],
                    $entity1['did']
                );
            }
            $agent2->wallet->did->store_their_did($entity1['did'], $entity1['verkey']);
            if (!$agent2->wallet->pairwise->is_pairwise_exists($entity1['did'])) {
                printf('#2');
                $agent2->wallet->pairwise->create_pairwise($entity1['did'], $entity2['did']);
            }
            // Prepare message.
            $trust_ping = new Message([
                '@id' => 'trust-ping-message-'. uniqid('', true),
                '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/trust_ping/1.0/ping',
                'comment' => 'Hi. Are you listening?',
                'response_requested' => true
            ]);
            $agent1->send_message(
                $trust_ping,
                $entity2['verkey'],
                $agent2_endpoint,
                $entity1['verkey'],
                []
            );
            $event = $agent2_listener->get_one(5);
            $msg = $event->getMessage();
            self::assertEquals('did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/trust_ping/1.0/ping', $msg->payload['@type']);
            self::assertEquals($trust_ping->id, $msg->id);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     */
    public function test_listener_restore_message(): void
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
            $agent2_endpoint = Conftest::get_endpoints($agent2->endpoints)[0]->address;
            $agent2_listener = $agent2->subscribe();
            // Exchange pairwise
            $agent1->wallet->did->store_their_did($entity2['did'], $entity2['verkey']);
            if (!$agent1->wallet->pairwise->is_pairwise_exists($entity2['did'])) {
                printf('#1');
                $agent1->wallet->pairwise->create_pairwise($entity2['did'], $entity1['did']);
            }
            $agent2->wallet->did->store_their_did($entity1['did'], $entity1['verkey']);
            if (!$agent2->wallet->pairwise->is_pairwise_exists($entity1['did'])) {
                $agent2->wallet->pairwise->create_pairwise($entity1['did'], $entity2['did']);
            }
            Message::registerMessageClass(TrustPingMessageUnderTest::class, 'trust_ping_test');
            $trust_ping = new Message([
                '@id' => 'trust-ping-message-' . uniqid('', true),
                '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/trust_ping_test/1.0/ping',
                'comment' => 'Hi. Are you listening?',
                'response_requested' => true
            ]);
            $agent1->send_message(
                $trust_ping,
                $entity2['verkey'],
                $agent2_endpoint,
                $entity1['verkey'],
                []
            );
            $event = $agent2_listener->get_one(5);
            $msg = $event->payload['message'];
            self::assertInstanceOf(TrustPingMessageUnderTest::class, $msg, 'Unexpected msg type: ' . gettype($msg));
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusRPCError
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     */
    public function test_agents_trust_ping(): void
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
            $agent1_listener = $agent1->subscribe();
            $agent2_listener = $agent2->subscribe();
            // Exchange pairwise
            $agent1->wallet->did->store_their_did($entity2['did'], $entity2['verkey']);
            if (!$agent1->wallet->pairwise->is_pairwise_exists($entity2['did'])) {
                printf('#1');
                $agent1->wallet->pairwise->create_pairwise($entity2['did'], $entity1['did']);
            }
            $agent2->wallet->did->store_their_did($entity1['did'], $entity1['verkey']);
            if (!$agent2->wallet->pairwise->is_pairwise_exists($entity1['did'])) {
                printf('#2');
                $agent2->wallet->pairwise->create_pairwise($entity1['did'], $entity2['did']);
            }
            // Prepare message
            $trust_ping = new Message([
                '@id' => 'trust-ping-message-' . uniqid('', true),
                '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/trust_ping/1.0/ping',
                'comment' => 'Hi. Are you listening?',
                'response_requested' => true
            ]);
            $agent1->send_message(
                $trust_ping,
                $entity2['verkey'],
                $agent2_endpoint,
                $entity1['verkey'],
                []
            );
            $event = $agent2_listener->get_one(5);
            $event = $event->payload;
            self::assertEquals($entity2['verkey'], $event['recipient_verkey']);
            self::assertEquals($entity1['verkey'], $event['sender_verkey']);
            $msg = $event['message']->payload;
            self::assertEquals('did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/trust_ping/1.0/ping', $msg['@type']);
            self::assertEquals($trust_ping->id, $msg['@id']);
            self::assertEquals($trust_ping->type, $msg['@type']);

            $ping_response = new Message([
                '@id' => 'e002518b-456e-b3d5-de8e-7a86fe472847',
                '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/trust_ping/1.0/ping_response',
                '~thread' => ['thid' => $trust_ping->id],
                'comment' => "Hi yourself. I'm here.",
            ]);
            $agent2->send_message(
                $ping_response,
                $entity1['verkey'],
                $agent1_endpoint,
                $entity2['verkey'],
                []
            );

            $event = $agent1_listener->get_one(5);
            $event = $event->payload;
            self::assertEquals($entity1['verkey'], $event['recipient_verkey']);
            self::assertEquals($entity2['verkey'], $event['sender_verkey']);
            $msg = $event['message']->payload;
            self::assertEquals('did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/trust_ping/1.0/ping_response', $msg['@type']);
            self::assertEquals($ping_response->id, $msg['@id']);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusFieldTypeError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_agents_crypto(): void
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
            $verkey_signer = 'FYmoFw55GeQH7SRFa37dkx1d2dZ3zUF8ckg7wmL7ofN4';
            $msg = b'message';
            // 1. Check sign
            $expected_signature = 'QRHbNQxHLEhBuYKbe3ReTUCNRDnGDYMJvABJFEuUSFU8EzS6orRzWjMf3fR4PSgM2Z5gqfsc1kg6vYpQCCb4bjB';
            $signature = $agent1->wallet->crypto->crypto_sign($verkey_signer, $msg);
            self::assertEquals($expected_signature, Encryption::bytes_to_b58($signature));
            // 2. Check verify
            $success = $agent2->wallet->crypto->crypto_verify($verkey_signer, $msg, $signature);
            self::assertTrue($success);
            // 3. Check verify with error
            $other_msg = 'other-message';
            $success = $agent2->wallet->crypto->crypto_verify($verkey_signer, $other_msg, $signature);
            self::assertFalse($success);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
}
