<?php


namespace Siruis\Tests\Threads\test_0037_verify_proof;


use PHPUnit\Framework\TestCase;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages\RequestPresentationMessage;
use Siruis\Agent\AriesRFC\feature_0037_present_proof\StateMachines\Prover;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Threaded;

class RunProver extends Threaded
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
    private $verifier;
    /**
     * @var string
     */
    private $master_secret_id;
    /**
     * @var bool
     */
    public $success;

    /**
     * RunProver constructor.
     * @param string $uri
     * @param string $credentials
     * @param P2PConnection $p2p
     * @param Pairwise $verifier
     * @param string $master_secret_id
     */
    public function __construct(
        string $uri, string $credentials, P2PConnection $p2p,
        Pairwise $verifier, string $master_secret_id
    )
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->verifier = $verifier;
        $this->master_secret_id = $master_secret_id;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $listener = Init::subscribe();
        $event = $listener->get_one();
        TestCase::assertNotNull($event->pairwise);
        TestCase::assertEquals($this->verifier->their->did, $event->pairwise->their->did);
        /** @var RequestPresentationMessage $request */
        $request = $event->getMessage();
        TestCase::assertInstanceOf(RequestPresentationMessage::class, $request);
        $ttl = 60;
        if ($request->getExpiresTime()) {
            $expire = new \DateTime($request->getExpiresTime());
            $delta = (new \DateTime())->diff($expire);
            if ($delta->s > 0) {
                $ttl = $delta->s;
            }
        }
        try {
            $ledger = Init::ledger('default');
            $machine = new Prover($this->verifier, $ledger, $ttl);
            $success = $machine->prove($request, $this->master_secret_id);
            if (!$success) {
                error_log('===================== Prover terminated with error ====================');
                if ($machine->problem_report) {
                    error_log(json_encode($machine->problem_report->payload));
                }
                error_log('=======================================================================');
            }
            $this->success = $success;
        } catch (\Exception $err) {
            print_r('==== Prover routine Exception: '.$err->getMessage());
        }
    }
}