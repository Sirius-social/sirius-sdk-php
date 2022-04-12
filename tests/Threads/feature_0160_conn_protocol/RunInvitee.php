<?php


namespace Siruis\Tests\Threads\feature_0160_conn_protocol;


use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\Invitation;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\StateMachines\Invitee;
use Siruis\Agent\Connections\Endpoint;
use Siruis\Agent\Pairwise\Me;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\ConnProtocol1060Test;

class RunInvitee extends \Threaded
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
     * @var Invitation
     */
    private $invitation;
    /**
     * @var string
     */
    private $my_label;
    /**
     * @var Me|null
     */
    private $me;
    /**
     * @var bool
     */
    private $replace_endpoints;
    /**
     * @var array|null
     */
    private $did_doc_extra;

    /**
     * RunInvitee constructor.
     * @param string $uri
     * @param string $credentials
     * @param P2PConnection $p2p
     * @param Invitation $invitation
     * @param string $my_label
     * @param Me|null $me
     * @param bool $replace_endpoints
     * @param array|null $did_doc_extra
     */
    public function __construct(
        string $uri, string $credentials, P2PConnection $p2p,
        Invitation $invitation, string $my_label,
        Me $me = null, bool $replace_endpoints = false, array $did_doc_extra = null
    )
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->invitation = $invitation;
        $this->my_label = $my_label;
        $this->me = $me;
        $this->replace_endpoints = $replace_endpoints;
        $this->did_doc_extra = $did_doc_extra;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        if (is_null($this->me)) {
            list($my_did, $my_verkey) = Init::DID()->create_and_store_my_did();
            $this->me = new Me($my_did, $my_verkey);
        }
        $endpoints_ = Init::endpoints();
        $my_endpoint = Conftest::get_endpoints($endpoints_)[0];
        $phpunit_configs = Conftest::phpunit_configs();
        if ($this->replace_endpoints) {
            $new_address = ConnProtocol1060Test::replace_url_components($my_endpoint->address, $phpunit_configs['test_suite_overlay_address']);
            $my_endpoint = new Endpoint($new_address, $my_endpoint->routingKeys, $my_endpoint->isDefault);
            $new_address = ConnProtocol1060Test::replace_url_components($this->invitation->payload['serviceEndpoint'], $phpunit_configs['old_agent_overlay_address']);
            $this->invitation->payload['serviceEndpoint'] = $new_address;
        }
        // Create and start machine
        $machine = new Invitee($this->me, $my_endpoint);
        list($ok, $pairwise) = $machine->create_connection($this->invitation, $this->my_label, $this->did_doc_extra);
        ConnProtocol1060Test::assertTrue($ok);
        Init::PairwiseList()->ensure_exists($pairwise);
    }
}