<?php


namespace Siruis\Tests\Threads\ConsensusSimple;


use Siruis\Agent\Consensus\Messages\InitRequestLedgerMessage;
use Siruis\Agent\Consensus\StateMachines\MicroLedgerSimpleConsensus;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\ConsensusSimpleTest;
use Threaded;

class RoutineOfLedgerCreationAcceptor extends Threaded
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

    public function __construct(string $uri, string $credentials, P2PConnection $p2p)
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $listener = Init::subscribe();
        $event = $listener->get_one();
        ConsensusSimpleTest::assertNotNull($event->pairwise);
        $propose = $event->getMessage();
        ConsensusSimpleTest::assertInstanceOf(InitRequestLedgerMessage::class, $propose);
        $machine = new MicroLedgerSimpleConsensus($event->pairwise->me);
        return $machine->accept_microledger($event->pairwise, $propose);
    }
}