<?php

namespace Siruis\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Messaging\Message;
use Siruis\Tests\Helpers\Conftest;

class TestPerformance extends TestCase
{
    const TEST_ITERATIONS = 5;

    /** @test */
    public function test_wallet_access()
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $a2b = Conftest::get_pairwise($agent1, $agent2);
            error_log('START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $pw = $agent1->wallet->pairwise->get_pairwise($a2b->their->did);
            }
            error_log('STOP');
            $stamp2 = new DateTime();
            $delta = date_diff($stamp2, $stamp1);
            self::assertNotFalse($delta);
            error_log('timeout:'.$delta->f);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /** @test */
    public function test_decode_message()
    {
        $agent1 = Conftest::agent1();
        $agent1->open();
        try {
            $seed = '000000000000000000000000000SEED1';
            $packed = b'{"protected": "eyJlbmMiOiAieGNoYWNoYTIwcG9seTEzMDVfaWV0ZiIsICJ0eXAiOiAiSldNLzEuMCIsICJhbGciOiAiQXV0aGNyeXB0IiwgInJlY2lwaWVudHMiOiBbeyJlbmNyeXB0ZWRfa2V5IjogInBKcW1xQS1IVWR6WTNWcFFTb2dySGx4WTgyRnc3Tl84YTFCSmtHU2VMT014VUlwT0RQWTZsMVVsaVVvOXFwS0giLCAiaGVhZGVyIjogeyJraWQiOiAiM1ZxZ2ZUcDZRNFZlRjhLWTdlVHVXRFZBWmFmRDJrVmNpb0R2NzZLR0xtZ0QiLCAic2VuZGVyIjogIjRlYzhBeFRHcWtxamd5NHlVdDF2a0poeWlYZlNUUHo1bTRKQjk1cGZSMG1JVW9KajAwWmswNmUyUEVDdUxJYmRDck8xeTM5LUhGTG5NdW5YQVJZWk5rZ2pyYV8wYTBQODJpbVdNcWNHc1FqaFd0QUhOcUw1OGNkUUYwYz0iLCAiaXYiOiAiVU1PM2o1ZHZwQnFMb2Rvd3V0c244WEMzTkVqSWJLb2oifX1dfQ==", "iv": "MchkHF2M-4hneeUJ", "ciphertext": "UgcdsV-0rIkP25eJuRSROOuqiTEXp4NToKjPMmqqtJs-Ih1b5t3EEbrrHxeSfPsHtlO6J4OqA1jc5uuD3aNssUyLug==", "tag": "sQD8qgJoTrRoyQKPeCSBlQ=="}';
            $agent1->wallet->did->create_and_store_my_did(null, $seed);
            error_log('START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $unpacked = $agent1->wallet->crypto->unpackMessage($packed);
            }
            error_log('STOP');
            $stamp2 = new DateTime();
            $delta = date_diff($stamp2, $stamp1);
            error_log('timeout: '.$delta->f);
            self::assertNotFalse($delta);
        } finally {
            $agent1->close();
        }
    }

    /** @test */
    public function test_send_message()
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $a2b = Conftest::get_pairwise($agent1, $agent2);
            $b2a = Conftest::get_pairwise($agent2, $agent1);
            $listener = $agent2->subscribe();

            error_log('START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $msg = new Message([
                    '@id' => 'message-id-'.uniqid(),
                    '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test/1.0/message',
                    'comment' => 'Hi. Are you listening?',
                    'response_requested' => true
                ]);
                $agent1->send_to($msg, $a2b);
                $resp = $listener->get_one();
                self::assertEquals($msg->payload['@id'], $resp->getMessage()->payload['@id']);
            }
            error_log('STOP');
            $stamp2 = new DateTime();
            $delta = $stamp2->diff($stamp1);
            error_log('timeout: '.$delta->f);
            self::assertNotFalse($delta);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /** @test */
    public function test_send_message_via_transport()
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $a2b = Conftest::get_pairwise($agent1, $agent2);
            $b2a = Conftest::get_pairwise($agent2, $agent1);
            $thread_id = 'thread-'.uniqid();
            $transport_for_a = $agent1->spawnThidPairwise($thread_id, $a2b);
            $transport_for_a->start();
            $transport_for_b = $agent2->spawnThidPairwise($thread_id, $b2a);
            $transport_for_b->start();

            error_log('START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $msg = new Message([
                    '@id' => 'message-id-'.uniqid(),
                    '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test/1.0/message',
                    'comment' => 'Hi. Are you listening?',
                    'response_requested' => true
                ]);
                $transport_for_a->send($msg);
                list($message, $sender_vk, $recip_vk) = $transport_for_b->get_one();
                self::assertEquals($msg->payload['@id'], $message->payload['@id']);
            }
            error_log('STOP');
            $stamp2 = new DateTime();
            $delta = $stamp2->diff($stamp1);
            error_log('timeout: '.$delta->f);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }

    /** @test */
    public function test_send_message_via_transport_via_websocket()
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent1->open();
        $agent2->open();
        try {
            $a2b = Conftest::get_pairwise($agent1, $agent2);
            $b2a = Conftest::get_pairwise($agent2, $agent1);
            $thread_id = 'thread-'.uniqid();
            $a2b->their->endpoint = str_ireplace('http://', 'ws://', $a2b->their->endpoint);
            $transport_for_a = $agent1->spawnThidPairwise($thread_id, $a2b);
            $transport_for_a->start();
            $transport_for_b = $agent2->spawnThidPairwise($thread_id, $b2a);
            $transport_for_b->start();

            error_log('START');
            $stamp1 = new DateTime();
            foreach (range(1, self::TEST_ITERATIONS) as $n) {
                $msg = new Message([
                    '@id' => 'message-id-'.uniqid(),
                    '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test/1.0/message',
                    'comment' => 'Hi. Are you listening?',
                    'response_requested' => true
                ]);
                $transport_for_a->send($msg);
                list($message, $sender_vk, $recip_vk) = $transport_for_b->get_one();
                self::assertEquals($msg->payload['@id'], $message->payload['@id']);
            }
            error_log('STOP');
            $stamp2 = new DateTime();
            $delta = $stamp2->diff($stamp1);
            error_log('timeout: '.$delta->f);
        } finally {
            $agent1->close();
            $agent2->close();
        }
    }
}
