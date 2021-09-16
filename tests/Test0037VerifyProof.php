<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Agent\Agent;
use Siruis\Agent\Codec;
use Siruis\Agent\Ledgers\CredentialDefinition;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Errors\IndyExceptions\AnoncredsMasterSecretDuplicateNameError;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\Helpers\Threads;
use Siruis\Tests\Threads\test_0037_verify_proof\RunProver;
use Siruis\Tests\Threads\test_0037_verify_proof\RunVerifier;

class Test0037VerifyProof extends TestCase
{
    /** @test
     * @throws AnoncredsMasterSecretDuplicateNameError|SiriusValidationError
     */
    public function test_sane()
    {
        $test_suite = Conftest::test_suite();
        $issuer = Conftest::agent1();
        $prover = Conftest::agent2();
        $verifier = Conftest::agent3();
        $prover_master_secret_name = Conftest::prover_master_secret_name();
        $issuer->open();
        $prover->open();
        $verifier->open();
        try {
            error_log('Establish pairwises');
            $i2p = Conftest::get_pairwise($issuer, $prover);
            $p2i = Conftest::get_pairwise($prover, $issuer);
            $v2p = Conftest::get_pairwise($verifier, $prover);
            $p2v = Conftest::get_pairwise($prover, $verifier);

            error_log('Register schema');
            list($did_issuer, $verkey_issuer) = [$i2p->me->did, $i2p->me->verkey];
            $schema_name = 'schema_'.uniqid();
            list($schema_id, $anoncred_schema) = $issuer->wallet->anoncreds->issuer_create_schema(
                $did_issuer, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']
            );
            $ledger = $issuer->ledger('default');
            list($ok, $schema) = $ledger->register_schema($anoncred_schema, $did_issuer);
            self::assertTrue($ok);

            error_log('Register credential def');
            list($ok, $cred_def) = $ledger->register_cred_def(
                new CredentialDefinition('TAG', $schema),
                $did_issuer
            );
            self::assertTrue($ok);

            error_log('Prepare prover');
            try {
                $prover->wallet->anoncreds->prover_create_master_secret($prover_master_secret_name);
            } catch (AnoncredsMasterSecretDuplicateNameError $err) {
                throw $err;
            }

            $prover_secret_id = $prover_master_secret_name;
            $cred_values = ['attr1' => 'Value-1', 'attr2' => 456, 'attr3' => 5.87];
            $cred_id = 'cred-id-'.uniqid();

            // Issue credential
            $offer = $issuer->wallet->anoncreds->issuer_create_credential_offer($cred_def->getId());
            list($cred_request, $cred_metadata) = $prover->wallet->anoncreds->prover_create_credential_req(
                $p2i->me->did, $offer, $cred_def->getBody(), $prover_secret_id
            );
            $encoded_cred_values = [];
            foreach ($cred_values as $key => $value) {
                $encoded_cred_values[$key] = [
                    'raw' => (string)$value, 'encoded' => Codec::encode($value)
                ];
            }
            $ret = $issuer->wallet->anoncreds->issuer_create_credential(
                $offer, $cred_request, $encoded_cred_values, null, null
            );
            list($cred, $cred_revoc_id, $revoc_reg_delta) = $ret;
            $prover->wallet->anoncreds->prover_store_credential(
                $cred_id, $cred_metadata, $cred, $cred_def->getBody(), null
            );
        } finally {
            $issuer->close();
            $prover->close();
            $verifier->close();
        }

        $prover = $test_suite->get_agent_params('agent2');
        $verifier = $test_suite->get_agent_params('agent3');

        // FIRE !!!
        $attr_referent_id = 'attr1_referent';
        $pred_referent_id = 'predicate1_referent';
        Hub::alloc_context($verifier['server_address'], $verifier['credentials'], $verifier['p2p']);
        $proof_request = [
            'nonce' => Init::AnonCreds()->generate_nonce(),
            'name' => 'Test ProofRequest',
            'version' => '0.1',
            'requested_attributes' => [
                $attr_referent_id => [
                    'name' => 'attr1',
                    'restrictions' => [
                        'issuer_id' => $did_issuer
                    ]
                ]
            ],
            'requested_predicates' => [
                $pred_referent_id => [
                    'name' => 'attr2',
                    'p_type' => '>=',
                    'p_value' => 100,
                    'restrictions' => [
                        'issuer_id' => $did_issuer
                    ]
                ]
            ]
        ];

        $coro_verifier = new RunVerifier(
            $verifier['server_address'], $verifier['credentials'], $verifier['p2p'], $v2p, $proof_request
        );
        $coro_prover = new RunProver(
            $prover['server_address'], $prover['credentials'], $prover['p2p'], $p2v, $prover_secret_id
        );

        error_log('Run state machines');
        Threads::run_threads([$coro_verifier, $coro_prover]);
        error_log('Finish state machines');
        $results = [$coro_verifier->success, $coro_prover->success];
        error_log(json_encode($results));
        self::assertCount(2, $results);
        foreach ($results as $result) {
            self::assertTrue($result);
        }
    }

    /** @test */
    public function test_multiple_provers()
    {
        $test_suite = Conftest::test_suite();
        $issuer = Conftest::agent1();
        $prover1 = Conftest::agent2();
        $verifier = Conftest::agent3();
        $prover2 = Conftest::agent4();
        $prover_master_secret_name = Conftest::prover_master_secret_name();
        $issuer->open();
        $prover1->open();
        $verifier->open();
        $prover2->open();
        try {
            error_log('Establish pairwises');
            $i_to_p1 = Conftest::get_pairwise($issuer, $prover1);
            $i_to_p2 = Conftest::get_pairwise($issuer, $prover2);
            $p1_to_i = Conftest::get_pairwise($prover1, $issuer);
            $p2_to_i = Conftest::get_pairwise($prover2, $issuer);
            $v_to_p1 = Conftest::get_pairwise($verifier, $prover1);
            $v_to_p2 = Conftest::get_pairwise($verifier, $prover2);
            $p1_to_v = Conftest::get_pairwise($prover1, $verifier);
            $p2_to_v = Conftest::get_pairwise($prover2, $verifier);

            error_log('Register schema');
            list($did_issuer, $verkey_issuer) = [$i_to_p1->me->did, $i_to_p1->me->verkey];
            $schema_name = 'schema_'.uniqid();
            list($schema_id, $anoncred_schema) = $issuer->wallet->anoncreds->issuer_create_schema(
                $did_issuer, $schema_name, '1.0', ['attr1', 'attr2', 'attr3']
            );
            $ledger = $issuer->ledger('default');
            list($ok, $schema) = $ledger->register_schema($anoncred_schema, $did_issuer);
            self::assertTrue($ok);

            error_log('Register credential def');
            list($ok, $cred_def) = $ledger->register_cred_def(
                new CredentialDefinition('TAG', $schema), $did_issuer
            );
            self::assertTrue($ok);

            error_log('Prepare Provers');
            /** @var Agent $prover */
            foreach ([$prover1, $prover2] as $prover) {
                try {
                    $prover->wallet->anoncreds->prover_create_master_secret($prover_master_secret_name);
                } catch (AnoncredsMasterSecretDuplicateNameError $error) {
                    throw $error;
                }
            }
            $cred_ids = [
                0 => 'cred-id-'.uniqid(),
                1 => 'cred-id-'.uniqid()
            ];
            $prover_did = [
                0 => $p1_to_i->me->did,
                1 => $p2_to_i->me->did
            ];
            foreach ([$prover1, $prover2] as $i => $prover) {
                $prover_secret_id = $prover_master_secret_name;
                $cred_values = ['attr1' => "Value-{$i}", 'attr2' => 200 + $i*10, 'attr3' => $i*1.5];
                $cred_id = $cred_ids[0];

                // Issue credential
                $offer = $issuer->wallet->anoncreds->issuer_create_credential_offer($cred_def->getId());
                list($cred_request, $cred_metadata) = $prover->wallet->anoncreds->prover_create_credential_req(
                    $prover_did[$i], $offer, $cred_def->getBody(), $prover_secret_id
                );
                $encoded_cred_values = [];
                foreach (array_values($cred_values) as $key => $value) {
                    $encoded_cred_values[$key] = ['raw' => (string)$value, 'encoded' => Codec::encode($value)];
                }
                $ret = $issuer->wallet->anoncreds->issuer_create_credential(
                    $offer, $cred_request, $encoded_cred_values, null, null
                );
                list($cred, $cred_revoc_id, $revoc_reg_delta) = $ret;
                $prover->wallet->anoncreds->prover_store_credential(
                    $cred_id, $cred_metadata, $cred, $cred_def->getBody(), null,
                );
            }
        } finally {
            $issuer->close();
            $prover1->close();
            $verifier->close();
            $prover2->close();
        }

        $prover1 = $test_suite->get_agent_params('agent1');
        $verifier = $test_suite->get_agent_params('agent2');
        $prover2 = $test_suite->get_agent_params('agent3');

        // FIRE !!!
        $attr_referent_id = 'attr1_referent';
        $pred_referent_id = 'predicate1_referent';
        Hub::alloc_context($verifier['server_address'], $verifier['credentials'], $verifier['p2p']);
        $proof_request = [
            'nonce' => Init::AnonCreds()->generate_nonce(),
            'name' => 'Test ProofRequest',
            'version' => '0.1',
            'requested_attributes' => [
                $attr_referent_id => [
                    'name' => 'attr1',
                    'restrictions' => [
                        'issuer_did' => $did_issuer
                    ]
                ]
            ],
            'requested_predicates' => [
                $pred_referent_id => [
                    'name' => 'attr2',
                    'p_type' => '>=',
                    'p_value' => 100,
                    'restrictions' => [
                        'issuer_did' => $did_issuer
                    ]
                ]
            ]
        ];

        foreach ([[$prover1, $v_to_p1, $p1_to_v], [$prover2, $v_to_p2, $p2_to_v]] as list($prover, $v2p, $p2v) ) {
            $coro_verifier = new RunVerifier(
                $verifier['server_address'], $verifier['credentials'], $verifier['p2p'],
                $v2p, $proof_request
            );
            $coro_prover = new RunProver(
                $verifier['server_address'], $verifier['credentials'], $verifier['p2p'],
                $v2p, $prover_master_secret_name
            );
            error_log('Run state machines');
            Threads::run_threads([$coro_verifier, $coro_prover]);
            error_log('Finish state machines');
            $results = [$coro_verifier->success, $coro_prover->success];
            error_log(json_encode($results));
            self::assertCount(2, $results);
            foreach ($results as $result) {
                self::assertTrue($result);
            }
        }
    }
}
