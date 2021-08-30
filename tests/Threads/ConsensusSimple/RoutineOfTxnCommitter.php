<?php


namespace Siruis\Tests\Threads\ConsensusSimple;


use Siruis\Agent\Consensus\StateMachines\MicroLedgerSimpleConsensus;
use Siruis\Agent\Microledgers\AbstractMicroledger;
use Siruis\Agent\Microledgers\Transaction;
use Siruis\Agent\Pairwise\Me;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Threaded;

class RoutineOfTxnCommitter extends Threaded
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
     * @var Me
     */
    private $me;
    /**
     * @var array
     */
    private $participants;
    private $ledger;
    /**
     * @var array
     */
    private $txns;

    public function __construct(string $uri, string $credentials, P2PConnection $p2p,
                                Me $me, array $participants, $ledger, array $txns)
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->me = $me;
        $this->participants = $participants;
        $this->ledger = $ledger;
        $this->txns = $txns;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $machine = new MicroLedgerSimpleConsensus($this->me);
        $txns = [];
        foreach ($this->txns as $txn) {
            array_push($txns, Transaction::create($txn));
        }
        if ($this->ledger instanceof AbstractMicroledger) {
            list($success, $txns) = $machine->commit($this->ledger, $this->participants, $txns);
        } else {
            $success = $machine->commit_in_parallel($this->ledger, $this->participants, $txns);
        }
        return [$success, $txns];
    }
}