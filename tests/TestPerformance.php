<?php

namespace Siruis\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use Siruis\Messaging\Message;
use Siruis\Tests\Helpers\Conftest;

class TestPerformance extends TestCase
{
    public const TEST_ITERATIONS = 100;

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_wallet_access(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $a2b = Conftest::get_pairwise($agent1, $agent2);
            printf('>START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $pw = $agent1->wallet->pairwise->get_pairwise($a2b->their->did);
            }
            printf('>STOP');
            $stamp2 = new DateTime();
            $delta = date_diff($stamp2, $stamp1);
            self::assertNotFalse($delta);
            printf('>timeout:'.$delta->f);
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
    public function test_decode_message(): void
    {
        $agent1 = Conftest::agent1();
        $agent1->open();
        try {
            $seed = '000000000000000000000000000SEED1';
            $packed = b'{"protected": "eyJlbmMiOiAieGNoYWNoYTIwcG9seTEzMDVfaWV0ZiIsICJ0eXAiOiAiSldNLzEuMCIsICJhbGciOiAiQXV0aGNyeXB0IiwgInJlY2lwaWVudHMiOiBbeyJlbmNyeXB0ZWRfa2V5IjogInBKcW1xQS1IVWR6WTNWcFFTb2dySGx4WTgyRnc3Tl84YTFCSmtHU2VMT014VUlwT0RQWTZsMVVsaVVvOXFwS0giLCAiaGVhZGVyIjogeyJraWQiOiAiM1ZxZ2ZUcDZRNFZlRjhLWTdlVHVXRFZBWmFmRDJrVmNpb0R2NzZLR0xtZ0QiLCAic2VuZGVyIjogIjRlYzhBeFRHcWtxamd5NHlVdDF2a0poeWlYZlNUUHo1bTRKQjk1cGZSMG1JVW9KajAwWmswNmUyUEVDdUxJYmRDck8xeTM5LUhGTG5NdW5YQVJZWk5rZ2pyYV8wYTBQODJpbVdNcWNHc1FqaFd0QUhOcUw1OGNkUUYwYz0iLCAiaXYiOiAiVU1PM2o1ZHZwQnFMb2Rvd3V0c244WEMzTkVqSWJLb2oifX1dfQ==", "iv": "MchkHF2M-4hneeUJ", "ciphertext": "UgcdsV-0rIkP25eJuRSROOuqiTEXp4NToKjPMmqqtJs-Ih1b5t3EEbrrHxeSfPsHtlO6J4OqA1jc5uuD3aNssUyLug==", "tag": "sQD8qgJoTrRoyQKPeCSBlQ=="}';
            $agent1->wallet->did->create_and_store_my_did(null, $seed);
            printf('>START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $pw = $agent1->wallet->crypto->unpack_message($packed);
            }
            printf('>STOP');
            $stamp2 = new DateTime();
            $delta = date_diff($stamp2, $stamp1);
            printf('>timeout: '.$delta->f);
            self::assertNotFalse($delta);
        } finally {
            $agent1->close();
        }
    }

    /**
     * @return void
     *
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
    public function test_send_message(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $a2b = Conftest::get_pairwise($agent1, $agent2);
            $listener = $agent2->subscribe();

            printf('>START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $msg = new Message([
                    '@id' => 'message-id-'.uniqid('', true),
                    '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test/1.0/message',
                    'comment' => 'Hi. Are you listening?',
                    'response_requested' => true
                ]);
                $agent1->send_to($msg, $a2b);
                $resp = $listener->get_one();
                self::assertEquals($msg->payload['@id'], $resp->getMessage()->payload['@id']);
            }
            printf('>STOP');
            $stamp2 = new DateTime();
            $delta = $stamp2->diff($stamp1);
            printf('>timeout: '.$delta->f);
            self::assertNotFalse($delta);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /**
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_send_message_via_transport(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $a2b = Conftest::get_pairwise($agent1, $agent2);
            $b2a = Conftest::get_pairwise($agent2, $agent1);
            $thread_id = 'thread-'.uniqid('', true);
            $transport_for_a = $agent1->spawnThidPairwise($thread_id, $a2b);
            $transport_for_a->start();
            $transport_for_b = $agent2->spawnThidPairwise($thread_id, $b2a);
            $transport_for_b->start();

            printf('>START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $msg = new Message([
                    '@id' => 'message-id-'.uniqid('', true),
                    '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test/1.0/message',
                    'comment' => 'Hi. Are you listening?',
                    'response_requested' => true
                ]);
                $transport_for_a->send($msg);
                [$message,,] = $transport_for_b->get_one();
                self::assertEquals($msg->payload['@id'], $message->payload['@id']);
            }
            printf('>STOP');
            $stamp2 = new DateTime();
            $delta = $stamp2->diff($stamp1);
            printf('>timeout: '.$delta->f);
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
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_send_message_via_transport_via_websocket(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $a2b = Conftest::get_pairwise($agent1, $agent2);
            $b2a = Conftest::get_pairwise($agent2, $agent1);
            $thread_id = 'thread-'.uniqid('', true);
            $a2b->their->endpoint = str_ireplace('http://', 'ws://', $a2b->their->endpoint);
            $transport_for_a = $agent1->spawnThidPairwise($thread_id, $a2b);
            $transport_for_a->start();
            $transport_for_b = $agent2->spawnThidPairwise($thread_id, $b2a);
            $transport_for_b->start();

            printf('>START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $msg = new Message([
                    '@id' => 'message-id-'.uniqid('', true),
                    '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test/1.0/message',
                    'comment' => 'Hi. Are you listening?',
                    'response_requested' => true
                ]);
                $transport_for_a->send($msg);
                [$message,,] = $transport_for_b->get_one();
                self::assertEquals($msg->payload['@id'], $message->payload['@id']);
            }
            printf('>STOP');
            $stamp2 = new DateTime();
            $delta = $stamp2->diff($stamp1);
            printf('>timeout: '.$delta->f);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
}
