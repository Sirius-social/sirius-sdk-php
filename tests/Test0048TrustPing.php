<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\AriesRFC\feature_0048_trust_ping\Ping;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
use Siruis\Tests\Helpers\Conftest;

class Test0048TrustPing extends TestCase
{
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
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_establish_connection(): void
    {
        $agent1 = Conftest::agent1();
        $agent2 = Conftest::agent2();
        $agent3 = Conftest::agent3();
        $agent1->open();
        $agent2->open();
        $agent3->open();
        try {
            [$did1, $verkey1] = $agent1->wallet->did->create_and_store_my_did();
            [$did2, $verkey2] = $agent2->wallet->did->create_and_store_my_did();
            $endpoint_address_2 = Conftest::get_endpoints($agent2->endpoints)[0]->address;
            $endpoint_address_3 = Conftest::get_endpoints($agent3->endpoints)[0]->address;

            $agent1->wallet->did->store_their_did($did2, $verkey2);
            $agent1->wallet->pairwise->create_pairwise($did2, $did1);
            $agent2->wallet->did->store_their_did($did1, $verkey1);
            $agent2->wallet->pairwise->create_pairwise($did1, $did2);

            $to = new Pairwise(new Me($did1, $verkey1), new Their($did2, 'Agent2', $endpoint_address_2, $verkey2));
            $listener2 = $agent2->subscribe();
            $ping = new Ping([], null, null, null, uniqid('', true));

            // Check OK
            $agent1->send_to($ping, $to);
            $event = $listener2->get_one();
            $recv = $event->getMessage();
            self::assertInstanceOf(Ping::class, $recv);
            self::assertEquals($ping->getComment(), $recv->getComment());

            // Check ERR
            $to = new Pairwise(
                new Me($did1, $verkey1),
                new Their($did2, 'Agent3', $endpoint_address_3, $verkey2)
            );
            $agent1->send_to($ping, $to);
        } finally {
            $agent1->close();
            $agent2->close();
            $agent3->close();
        }
    }
}
