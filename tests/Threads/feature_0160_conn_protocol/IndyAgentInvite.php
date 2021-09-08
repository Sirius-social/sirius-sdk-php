<?php


namespace Siruis\Tests\Threads\feature_0160_conn_protocol;


use Siruis\Tests\Helpers\IndyAgent;

class IndyAgentInvite extends \Threaded
{
    /**
     * @var string
     */
    private $invitation_url;
    /**
     * @var string|null
     */
    private $for_did;
    /**
     * @var int|null
     */
    private $ttl;
    /**
     * @var IndyAgent
     */
    private $indyAgent;

    public function __construct(IndyAgent $indyAgent, string $invitation_url, string $for_did = null, int $ttl = null)
    {
        $this->invitation_url = $invitation_url;
        $this->for_did = $for_did;
        $this->ttl = $ttl;
        $this->indyAgent = $indyAgent;
    }

    public function work()
    {
        $this->indyAgent->invite($this->invitation_url);
    }
}