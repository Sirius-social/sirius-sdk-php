<?php


namespace Siruis\Tests\Threads\ConsensusSimple;


use Siruis\Agent\Consensus\Messages\ProposeParallelTransactionsMessage;
use Siruis\Agent\Consensus\Messages\ProposeTransactionsMessage;
use Siruis\Agent\Consensus\StateMachines\MicroLedgerSimpleConsensus;
use Siruis\Agent\Microledgers\Transaction;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\ConsensusSimpleTest;

class RoutineOfTxnAcceptor extends \Threaded
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
     * @var Transaction[]|null
     */
    private $txns;

    public function __construct(string $uri, string $credentials, P2PConnection $p2p, array $txns = null)
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->txns = $txns;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $listener = Init::subscribe();
        while (true) {
            $event = $listener->get_one();
            ConsensusSimpleTest::assertNotNull($event->pairwise);
            $propose = $event->getMessage();
            if ($propose instanceof ProposeTransactionsMessage) {
                if ($this->txns) {
                    $propose->transactions = $this->txns;
                }
                $machine = new MicroLedgerSimpleConsensus($event->pairwise->me);
                return $machine->accept_commit($event->pairwise, $propose);
            } elseif ($propose instanceof ProposeParallelTransactionsMessage) {
                $machine = new  MicroLedgerSimpleConsensus($event->pairwise->me);
                return $machine->accept_commit_parallel($event->pairwise, $propose);
            }
        }
    }
}