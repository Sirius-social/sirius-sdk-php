<?php

namespace Siruis\Tests\Threads\test_0036_issue_credential;

use DateTime;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\OfferCredentialMessage;
use Siruis\Agent\AriesRFC\feature_0036_issue_credential\StateMachines\Holder;
use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;
use Threaded;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotNull;

class RunHolder extends Threaded
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
     * @var \Siruis\Encryption\P2PConnection
     */
    private $p2p;
    /**
     * @var mixed
     */
    public $result;
    /**
     * @var \Siruis\Agent\Pairwise\Pairwise
     */
    private $issuer;
    /**
     * @var string
     */
    private $master_secret_id;


    public function __construct(
        string $uri, string $credentials, P2PConnection $p2p, Pairwise $issuer,
        string $master_secret_id
    )
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->issuer = $issuer;
        $this->master_secret_id = $master_secret_id;
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Exception
     */
    public function work(): void
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $listener = Init::subscribe();
        $event = $listener->get_one();
        assertNotNull($event->pairwise);
        /** @var OfferCredentialMessage $offer */
        $offer = $event->getMessage();
        assertInstanceOf(OfferCredentialMessage::class, $offer);
        $ttl = 60;
        if ($offer->getExpiresTime()) {
            $expire = Utils::str_to_utc($offer->getExpiresTime(), false);
            $delta = (new DateTime)->diff($expire);
            if ($delta->s > 0) {
                $ttl = $delta->s;
            }
        }
        $machine = new Holder($this->issuer, $ttl);
        [$success, $cred_id] = $machine->accept($offer, $this->master_secret_id, 'Hello Iam holder');
        $this->result = [$success, $cred_id];
    }
}