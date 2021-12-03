<?php

namespace Siruis\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use Siruis\Agent\Agent\Agent;
use Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport;
use Siruis\Base\WebSocketConnector;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Messaging\Message;
use Siruis\Tests\Helpers\Conftest;

class TestTimeouts extends TestCase
{
    public function test_agent_rcv_timeout(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent4');
        $timeout = 3;
        // Check-1: check timeout error if ttl limit was set globally
        $conn_with_global_setting = new WebSocketConnector(
            $params['server_address'],
            '/rpc',
            $params['credentials'],
            $timeout
        );
        $conn_with_global_setting->open();
        try {
            $context = $conn_with_global_setting->read();
            self::assertNotNull($context);
            $this->expectException(SiriusTimeoutIO::class);
            $conn_with_global_setting->read();
        } finally {
            $conn_with_global_setting->close();
        }

        // Check-2: check timeout error if ttl limit was set locally
        $conn_with_local_setting = new WebSocketConnector(
            $params['server_address'],
            '/rpc',
            $params['credentials'],
            10000
        );
        $conn_with_local_setting->open();
        try {
            $context = $conn_with_local_setting->read();
            self::assertNotNull($context);
            $conn_with_local_setting->read($timeout);
            $this->expectException(SiriusTimeoutIO::class);
        } finally {
            $conn_with_local_setting->close();
        }
    }

    public function test_coprotocol_timeout()
    {
        $test_suite = Conftest::test_suite();
        $params_me = $test_suite->get_agent_params('agent4');
        $params_their = $test_suite->get_agent_params('agent3');
        $timeout = 5;
        $me = new Agent(
            $params_me['server_address'],
            $params_me['credentials'],
            $params_me['p2p'],
            1000,
            null, 'agent4'
        );
        $their = new Agent(
            $params_their['server_address'],
            $params_their['credentials'],
            $params_their['p2p'],
            $timeout, null,
            'agent3'
        );
        $me->open();
        $their->open();
        try {
            $p2p = Conftest::get_pairwise($me, $their);
            $thread_id = 'thread-'.uniqid();
            $co = $me->spawnThidPairwise($thread_id, $p2p);
            self::assertInstanceOf(ThreadBasedCoProtocolTransport::class, $co);
            $co->start(['test_protocol'], $timeout);
            $msg = new Message([
                '@type' => 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/test_protocol/1.0/request-1',
                'content' => 'Request'
            ]);
            $stamp1 = new DateTime();
            [$ok, $resp] = $co->switch($msg);
            self::assertFalse($ok);
            $this->expectException(SiriusTimeoutIO::class);
            $co->get_one();
            $stamp2 = new DateTime();
            $stamps_delta = date_diff($stamp2, $stamp1);
            self::assertGreaterThanOrEqual(4, $stamps_delta, "Timeout {$stamps_delta->format('U')}");
            self::assertGreaterThanOrEqual(6, $stamps_delta, "Timeout {$stamps_delta->format('U')}");
        } finally {
            $me->close();
            $their->close();
        }
    }
}
