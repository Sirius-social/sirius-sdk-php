<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\ProposedAttrib;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\StateMachines\Holder;
use Siruis\Agent\Ledgers\CredentialDefinition;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
use Siruis\Agent\Wallet\Abstracts\Ledger\NYMRole;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\Helpers\Threads;
use Siruis\Tests\Threads\test_0036_issue_credential\RunHolder;
use Siruis\Tests\Threads\test_0036_issue_credential\RunIssuer;

class Test0036IssueCredential extends TestCase
{
    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     * @throws \SodiumException
     */
    public function test_sane(): void
    {
        $test_suite = Conftest::test_suite();
        $issuer = Conftest::agent1();
        $holder = Conftest::agent2();
        $prover_master_secret_name = Conftest::prover_master_secret_name();
        $issuer->open();
        $holder->open();
        try {
            $i2h = Conftest::get_pairwise($issuer, $holder);
            $h2i = Conftest::get_pairwise($holder, $issuer);

            [$did_issuer,] = [$i2h->me->did, $i2h->me->verkey];
            $schema_name = 'schema_' . uniqid('', true);
            [, $anoncred_schema] = $issuer->wallet->anoncreds->issuer_create_schema(
                $did_issuer, $schema_name, '1.0', ['attr1', 'attr2', 'attr3', 'attr4']
            );
            $ledger = $issuer->ledger('default');
            [$ok, $schema] = $ledger->register_schema($anoncred_schema, $did_issuer);
            self::assertTrue($ok);

            [$ok, $cred_def] = $ledger->register_cred_def(
                new CredentialDefinition('TAG', $schema),
                $did_issuer
            );
            self::assertTrue($ok);

            printf('Prepare Holder');
            $holder->wallet->anoncreds->prover_create_master_secret($prover_master_secret_name);
        } finally {
            $issuer->close();
            $holder->close();
        }

        $issuer = $test_suite->get_agent_params('agent1');
        $holder = $test_suite->get_agent_params('agent2');
        $holder_secret_id = $prover_master_secret_name;

        $cred_id = 'cred-id-' . uniqid('', true);
        $coro_issuer = new RunIssuer(
            $issuer['server_addres'], $issuer['credentials'], $issuer['p2p'],
            $i2h,
            ['attr1' => 'Value-1', 'attr2' => 567, 'attr3' => 5.7, 'attr4' => 'base64'],
            $schema, $cred_def,
            [
                new ProposedAttrib('attr1', 'Value-1', 'text/plain'),
                new ProposedAttrib('attr4', 'base64', 'image/png')
            ],
            null, $cred_id
        );
        $coro_holder = new RunHolder(
            $holder['server_address'], $holder['credentials'], $holder['p2p'],
            $h2i,
            $holder_secret_id
        );

        Threads::run_threads([$coro_issuer, $coro_holder]);
        $results = [$coro_issuer->result, $coro_holder->result];
        $cred_id = null;
        foreach ($results as $res) {
            if (is_array($res)) {
                [$ok, $cred_id] = $res;
            } else {
                $ok = $res;
            }
            self::assertTrue($ok);
        }

        self::assertNotNull($cred_id);
        Hub::alloc_context($holder['server_address'], $holder['credentials'], $holder['p2p']);
        $cred = Init::AnonCreds()->prover_get_credential($cred_id);
        self::assertNotNull($cred);
        $mime_types = Holder::get_mime_types($cred_id);
        self::assertCount(2, $mime_types);
        self::assertEquals('text/plain', $mime_types['attr1']);
        self::assertEquals('image/png', $mime_types['attr4']);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function test_issuer_back_compatibility(): void
    {
        $indy_agent = Conftest::indy_agent();
        $test_suite = Conftest::test_suite();
        $issuer = Conftest::agent1();
        $configs = Conftest::phpunit_configs();
        $issuer->open();
        try {
            $endpoint_issuer = Conftest::get_endpoints($issuer->endpoints)[0]->address;
            [$did_issuer, $verkey_issuer] = $issuer->wallet->did->create_and_store_my_did();
            [$did_holder, $verkey_holder] = $indy_agent->create_and_store_my_did();
            $pairwise_for_issuer = new Pairwise(
                new Me($did_issuer, $verkey_issuer),
                new Their($did_holder, 'Holder', $indy_agent->endpoint['url'], $verkey_holder)
            );
            $pairwise_for_holder = new Pairwise(
                new Me($did_holder, $verkey_holder),
                new Their($did_issuer, 'Issuer', $endpoint_issuer, $verkey_issuer)
            );
            $pairwise_for_issuer->their->setNetLoc(str_replace('http://', '', $configs['old_agent_overlay_address']));
            $pairwise_for_holder->their->setNetLoc(str_replace('http://', '', $configs['test_suite_overlay_address']));
            $indy_agent->create_pairwise_statically($pairwise_for_holder);
            $issuer->wallet->did->store_their_did($did_holder, $verkey_holder);
            $issuer->pairwise_list->ensure_exists($pairwise_for_issuer);

            $schema_name = 'schema_' . uniqid('', true);
            [, $anoncred_schema] = $issuer->wallet->anoncreds->issuer_create_schema(
                $did_issuer, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']
            );
            $ledger = $issuer->ledger('default');
            [$ok, ] = $issuer->wallet->ledger->write_nym(
                'default', 'Th7MpTaRZVRYnPiabds81Y',
                $did_issuer, $verkey_issuer, 'Issuer', NYMRole::TRUST_ANCHOR()
            );
            self::assertTrue($ok);
            [$ok, $schema] = $ledger->register_schema($anoncred_schema, $did_issuer);
            self::assertTrue($ok);

            [$ok, $cred_def] = $ledger->register_cred_def(
                new CredentialDefinition('TAG', $schema),
                $did_issuer
            );
            self::assertTrue($ok);
        } finally {
            $issuer->close();
        }

        $issuer = $test_suite->get_agent_params('agent1');
        $cred_id = 'cred-id-' . uniqid('', true);
        $coro_issuer = new RunIssuer(
            $issuer['server_address'], $issuer['credentials'], $issuer['p2p'],
            $pairwise_for_issuer,
            ['attr1' => 'Value-1', 'attr2' => 567, 'attr3' => 5.7],
            $schema, $cred_def, null, null, $cred_id
        );

        Threads::run_threads([$coro_issuer]);
        self::assertTrue($coro_issuer->result);
    }

    public function test_holder_back_compatibility(): void
    {

    }
}
