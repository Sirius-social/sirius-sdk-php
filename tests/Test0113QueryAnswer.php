<?php


namespace Siruis\Tests;


use PHPUnit\Framework\TestCase;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\Helpers\Threads;
use Siruis\Tests\Threads\test_0113_query_answer\Requester;
use Siruis\Tests\Threads\test_0113_query_answer\Responder;

class Test0113QueryAnswer extends TestCase
{
    /** @test */
    public function test_sane()
    {
        $requester = Conftest::agent1();
        $responder = Conftest::agent2();
        $test_suite = Conftest::test_suite();
        $requester->open();
        $responder->open();
        try {
            $req2resp = Conftest::get_pairwise($requester, $responder);
        } finally {
            $requester->close();
            $responder->close();
        }

        $params_req = $test_suite->get_agent_params('agent1');
        $params_resp = $test_suite->get_agent_params('agent2');

        $coro_requester = new Requester($params_req['server_address'], $params_req['credentials'], $params_req['p2p'], $req2resp);
        $coro_responder = new Responder($params_resp['server_address'], $params_resp['credentials'], $params_resp['p2p']);

        print_r('Run state machines\n');
        Threads::run_threads([$coro_requester, $coro_responder]);
        print_r('Finish state machines\n');
        self::assertTrue($coro_requester->success);
        self::assertTrue($coro_responder->success);
    }
}