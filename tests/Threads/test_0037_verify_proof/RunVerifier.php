<?php


namespace Siruis\Tests\Threads\test_0037_verify_proof;


use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\AttribTranslation;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\StateMachines\Verifier;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Threaded;

class RunVerifier extends Threaded
{
    /**
     * @var string
     */
    private $uri;
    /**
     * @var string
     */
    private $credentials;
    /**
     * @var P2PConnection
     */
    private $p2p;
    /**
     * @var Pairwise
     */
    private $prover;
    /**
     * @var array
     */
    private $proof_request;
    /**
     * @var AttribTranslation[]|null
     */
    private $translation;
    /**
     * @var bool
     */
    public $success;

    public function __construct(
        string $uri, string $credentials, P2PConnection $p2p,
        Pairwise $prover, array $proof_request, array $translation = null
    )
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->prover = $prover;
        $this->proof_request = $proof_request;
        $this->translation = $translation;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $ledger = Init::ledger('default');
        $machine = new Verifier($this->prover, $ledger);
        $success = $machine->verify(
            $this->proof_request, $this->translation, 'I am Verifier', null, '1.0'
        );
        if (!$success) {
            error_log('===================== Verifier terminated with error ====================');
            if ($machine->problem_report) {
                error_log(json_encode($machine->problem_report->payload));
            }
            error_log('=======================================================================');
        }
        $this->success = $success;
    }
}