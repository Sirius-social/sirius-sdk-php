<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\Invitation;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
use Siruis\Helpers\UrlHelper;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\Helpers\Threads;
use Siruis\Tests\Threads\feature_0160_conn_protocol\IndyAgentInvite;
use Siruis\Tests\Threads\feature_0160_conn_protocol\ReadEvents;
use Siruis\Tests\Threads\feature_0160_conn_protocol\RunInvitee;
use Siruis\Tests\Threads\feature_0160_conn_protocol\RunInviter;

class ConnProtocol1060Test extends TestCase
{
    public static function replace_url_components(string $url, string $base = null): string
    {
        $ret = $url;
        if ($base) {
            $parsed = parse_url($url);
            $components = array_values($parsed);
            $components[1] = parse_url($base)['host'];
            $ret = UrlHelper::unparse_url($components);
        }
        return $ret;
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_establish_connection(): void
    {
        $test_suite = Conftest::test_suite();
        $inviter = $test_suite->get_agent_params('agent1');
        $invitee = $test_suite->get_agent_params('agent2');

        // Get endpoints
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        $inviter_endpoint_address = Conftest::get_endpoints(Init::endpoints())[0]->address;
        $connection_key = Init::Crypto()->create_key();
        $invitation = new Invitation([], 'Inviter', [$connection_key], $inviter_endpoint_address);

        // Init me
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        [$did, $verkey] = Init::DID()->create_and_store_my_did();
        $inviter_me = new Me($did, $verkey);
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        [$did, $verkey] = Init::DID()->create_and_store_my_did();
        $invitee_me = new Me($did, $verkey);

        Threads::run_threads([
            new RunInviter(
                $inviter['server_address'], $inviter['credentials'], $inviter['p2p'], $connection_key, $inviter_me
            ),
            new RunInvitee(
                $invitee['server_address'], $invitee['credentials'], $invitee['p2p'], $invitation, 'Invitee', $invitee_me
            )
        ]);

        // Check for Inviter
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        $pairwise = Init::PairwiseList()->load_for_verkey($invitee_me->verkey);
        self::assertNotNull($pairwise);
        self::assertEquals($invitee_me->did, $pairwise->their->did);

        // Check for Invitee
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        $pairwise = Init::PairwiseList()->load_for_verkey($inviter_me->verkey);
        self::assertNotNull($pairwise);
        self::assertEquals($inviter_me->did, $pairwise->their->did);
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_update_pairwise_metadata(): void
    {
        $test_suite = Conftest::test_suite();
        $inviter = $test_suite->get_agent_params('agent1');
        $invitee = $test_suite->get_agent_params('agent2');

        // Get endpoints
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        $inviter_endpoint_address = Conftest::get_endpoints(Init::endpoints())[0]->address;
        $connection_key = Init::Crypto()->create_key();
        $invitation = new Invitation([], 'Inviter', [$connection_key], $inviter_endpoint_address);
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        $invitee_endpoint_address = Conftest::get_endpoints(Init::endpoints())[0]->address;

        // Init me
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        [$did, $verkey] = Init::DID()->create_and_store_my_did();
        $inviter_side = new Me($did, $verkey);
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        [$did, $verkey] = Init::DID()->create_and_store_my_did();
        $invitee_side = new Me($did, $verkey);

        // Manually set pairwise list
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        Init::DID()->store_their_did($invitee_side->did, $invitee_side->verkey);
        $p = new Pairwise(
            $inviter_side,
            new Their(
                $invitee_side->did, 'Invitee', $invitee_endpoint_address, $invitee_side->verkey
            )
        );
        Init::PairwiseList()->create($p);

        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        Init::DID()->store_their_did($inviter_side->did, $inviter_side->verkey);
        $p = new Pairwise(
            $invitee_side,
            new Their(
                $inviter_side->did, 'Inviter', $inviter_endpoint_address, $inviter_side->verkey
            )
        );
        Init::PairwiseList()->create($p);


        Threads::run_threads([
            new RunInviter(
                $inviter['server_address'], $inviter['credentials'], $inviter['p2p'], $connection_key, $invitee_side
            ),
            new RunInvitee(
                $invitee['server_address'], $invitee['credentials'], $invitee['p2p'], $invitation, 'Invitee', $invitee_side
            )
        ]);

        // Check for inviter
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        $pairwise = Init::PairwiseList()->load_for_verkey($invitee_side->verkey);
        self::assertNotEquals([], $pairwise->metadata);
        self::assertNotNull($pairwise->metadata);

        // Check for Invitee
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        $pairwise = Init::PairwiseList()->load_for_verkey($inviter_side->verkey);
        self::assertNotEquals([], $pairwise->metadata);
        self::assertNotNull($pairwise->metadata);
    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     * @throws \SodiumException
     */
    public function test_invitee_back_compatibility(): void
    {
        $indy_agent = Conftest::indy_agent();
        $test_suite = Conftest::test_suite();
        $their_invitation = $indy_agent->create_invitation('Test Invitee');
        $invitation = Invitation::fromUrl($their_invitation['url']);
        $invitee = $test_suite->get_agent_params('agent1');

        // Init invitee
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        [$did, $verkey] = Init::DID()->create_and_store_my_did();
        $invitee_side = new Me($did, $verkey);

        $run_invitee = new RunInvitee(
            $invitee['server_address'], $invitee['credentials'], $invitee['p2p'], $invitation, 'Invitee', $invitee_side
        );
        $run_invitee->work();
        $read_events = new ReadEvents(
            $invitee['server_address'], $invitee['credentials'], $invitee['p2p']
        );
        $read_events->work();
        $invitation_pairwise = null;
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        foreach (Init::PairwiseList()->enumerate() as $i => $pairwise) {
            if ($pairwise->me->did === $invitee_side->did) {
                $invitation_pairwise = $pairwise;
                break;
            }
        }
        self::assertNotNull($invitation_pairwise);
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_inviter_back_compatibility(): void
    {
        $indy_agent = Conftest::indy_agent();
        $test_suite = Conftest::test_suite();
        $agent1 = Conftest::agent1();
        $inviter = $test_suite->get_agent_params('agent1');
        $phpunit_configs = Conftest::phpunit_configs();
        // Init inviter
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        $inviter_endpoint_address = Conftest::get_endpoints(Init::endpoints())[0]->address;
        $connection_key = Init::Crypto()->create_key();
        $inviter_endpoint_address = self::replace_url_components($inviter_endpoint_address, $phpunit_configs['test_suite_overlay_address']);
        $invitation = new Invitation([], 'Inviter', [$connection_key], $inviter_endpoint_address);
        $invitation_url = $invitation->getInvitationUrl();
        [$did, $verkey] = Init::DID()->create_and_store_my_did();
        $inviter_side = new Me($did, $verkey);

        Threads::run_threads([
            new RunInviter(
                $inviter['server_address'], $inviter['credentials'], $inviter['p2p'], $connection_key, $inviter_side, true
            ),
            new IndyAgentInvite($indy_agent, $invitation_url)
        ]);

        $invited_pairwise = null;
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        foreach (Init::PairwiseList()->enumerate() as $i => $p) {
            self::assertInstanceOf(Pairwise::class, $p);
            if ($p->me->did === $inviter_side->did) {
                $invited_pairwise = $p;
                break;
            }
        }
        self::assertNotNull($invited_pairwise);
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_did_doc_extra_fields(): void
    {
        $test_suite = Conftest::test_suite();
        $inviter = $test_suite->get_agent_params('agent1');
        $invitee = $test_suite->get_agent_params('agent2');

        // Get endpoints
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        $inviter_endpoint_address = Conftest::get_endpoints(Init::endpoints())[0]->address;
        $connection_key = Init::Crypto()->create_key();
        $invitation = new Invitation([], 'Inviter', [$connection_key], $inviter_endpoint_address);

        // Init me
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        [$did, $verkey] = Init::DID()->create_and_store_my_did();
        $inviter_me = new Me($did, $verkey);
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        [$did, $verkey] = Init::DID()->create_and_store_my_did();
        $invitee_me = new Me($did, $verkey);

        Threads::run_threads([
            new RunInviter(
                $inviter['server_address'], $inviter['credentials'], $inviter['p2p'], $connection_key, $inviter_me,
                false,
                [
                    'creator' => ['@id' => 'uuid-xxx-yyy'],
                    'extra' => 'Any'
                ]
            ),
            new RunInvitee(
                $invitee['server_address'], $invitee['credentials'], $invitee['p2p'], $invitation, 'Invitee', $invitee_me,
                false,
                [
                    'creator' => ['@id' => 'uuid-www-zzz'],
                    'extra' => 'Test'
                ]
            )
        ]);

        // Check for Inviter
        Hub::alloc_context($inviter['server_address'], $inviter['credentials'], $inviter['p2p']);
        $pairwise = Init::PairwiseList()->load_for_verkey($invitee_me->verkey);
        self::assertNotNull($pairwise);
        self::assertEquals($invitee_me->did, $pairwise->their->did);
        self::assertNotNull($pairwise->me->did_doc);
        self::assertEquals(['@id' => 'uuid-xxx-yyy'], $pairwise->me->did_doc['creator']);
        self::assertEquals('Any', $pairwise->me->did_doc['extra']);
        self::assertNotNull($pairwise->their->did_doc);
        self::assertEquals(['@id' => 'uuid-www-zzz'], $pairwise->their->did_doc['creator']);
        self::assertEquals('Test', $pairwise->their->did_doc['extra']);
        // Check for Invitee
        Hub::alloc_context($invitee['server_address'], $invitee['credentials'], $invitee['p2p']);
        $pairwise = Init::PairwiseList()->load_for_verkey($invitee_me->verkey);
        self::assertNotNull($pairwise);
        self::assertEquals($inviter_me->did, $pairwise->their->did);
        self::assertNotNull($pairwise->me->did_doc);
        self::assertEquals(['@id' => 'uuid-xxx-yyy'], $pairwise->their->did_doc['creator']);
        self::assertEquals('Any', $pairwise->their->did_doc['extra']);
        self::assertNotNull($pairwise->their->did_doc);
        self::assertEquals(['@id' => 'uuid-www-zzz'], $pairwise->me->did_doc['creator']);
        self::assertEquals('Test', $pairwise->me->did_doc['extra']);
    }
}
