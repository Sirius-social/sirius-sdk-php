<?php


namespace Siruis\Tests\Threads\feature_0160_conn_protocol;


use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnRequest;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\StateMachines\Inviter;
use Siruis\Agent\Connections\Endpoint;
use Siruis\Agent\Pairwise\Me;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Siruis\Tests\Helpers\Conftest;
use Siruis\Tests\ConnProtocol1060Test;

class RunInviter extends \Threaded
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
     * @var string
     */
    private $expected_connection_key;
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
     * RunInviter constructor.
     * @param string $uri
     * @param string $credentials
     * @param P2PConnection $p2p
     * @param string $expected_connection_key
     * @param Me|null $me
     * @param bool $replace_endpoints
     * @param array|null $did_doc_extra
     */
    public function __construct(
        string $uri, string $credentials, P2PConnection $p2p, string $expected_connection_key,
        Me $me = null, bool $replace_endpoints = false, array $did_doc_extra = null
    )
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->expected_connection_key = $expected_connection_key;
        $this->me = $me;
        $this->replace_endpoints = $replace_endpoints;
        $this->did_doc_extra = $did_doc_extra;
    }

    public function work()
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $endpoints = Init::endpoints();
        $my_endpoint = Conftest::get_endpoints($endpoints)[0];
        $phpunit_configs = Conftest::phpunit_configs();
        if ($this->replace_endpoints) {
            $new_address = ConnProtocol1060Test::replace_url_components($my_endpoint->address, $phpunit_configs['test_suite_overlay_address']);
            $my_endpoint = new Endpoint($new_address, $my_endpoint->routingKeys, $my_endpoint->isDefault);
        }
        $listener = Init::subscribe();
        $event = $listener->get_one();
        $connection_key = $event->getRecipientVerkey();
        if ($this->expected_connection_key == $connection_key) {
            $request = $event->getMessage();
            ConnProtocol1060Test::assertInstanceOf(ConnRequest::class, $request);
            if ($this->replace_endpoints) {
                $request->payload['connection']['did_doc']['service'][0]['serviceEndpoint'] = ConnProtocol1060Test::replace_url_components(
                    $request->payload['connection']['did_doc']['service'][0]['serviceEndpoint'],
                    $phpunit_configs['old_agent_overlay_address']
                );
            }
            // Setup state machine
            if (is_null($this->me)) {
                list($my_did, $my_verkey) = Init::DID()->create_and_store_my_did();
                $this->me = new Me($my_did, $my_verkey);
            }
            // Create connection
            $machine = new Inviter($this->me, $connection_key, $my_endpoint);
            list($ok, $pairwise) = $machine->create_connection($request, $this->did_doc_extra);
            ConnProtocol1060Test::assertTrue($ok);
            Init::PairwiseList()->ensure_exists($pairwise);
        }
    }
}