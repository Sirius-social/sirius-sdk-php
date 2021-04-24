<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;

class TestVerifyProof extends TestCase
{
    public function run_verifier(
        string $uri,
        string $credentials,
        P2PConnection $p2p,
        Pairwise $prover,
        array $proof_request,
        array $translation
    ) {
        Hub::context($uri, $credentials, $p2p);
        $ledger = Init::ledger('default');
    }

    /** @test */
    public function test_sane()
    {
    }
}
