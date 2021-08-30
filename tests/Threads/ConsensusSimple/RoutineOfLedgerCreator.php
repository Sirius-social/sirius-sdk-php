<?php


namespace Siruis\Tests\Threads\ConsensusSimple;


use Siruis\Agent\Consensus\StateMachines\MicroLedgerSimpleConsensus;
use Siruis\Agent\Microledgers\Transaction;
use Siruis\Agent\Pairwise\Me;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Threaded;

class RoutineOfLedgerCreator extends Threaded
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
    /**
     * @var string
     */
    private $ledger_name;
    /**
     * @var array
     */
    private $genesis;

    public function __construct(string $uri, string $credentials, P2PConnection $p2p,
                                Me $me, array $participants, string $ledger_name, array $genesis)
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->me = $me;
        $this->participants = $participants;
        $this->ledger_name = $ledger_name;
        $this->genesis = $genesis;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $machine = new MicroLedgerSimpleConsensus($this->me);
        $genesis = [];
        foreach ($this->genesis as $item) {
            array_push($genesis, Transaction::create($item));
        }
        list($success, $ledger) = $machine->init_microledger($this->ledger_name, $this->participants, $genesis);
        return [$success, $ledger];
    }
}