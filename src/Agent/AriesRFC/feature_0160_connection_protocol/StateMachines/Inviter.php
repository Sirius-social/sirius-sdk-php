<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol\StateMachines;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0048_trust_ping\Ping;
use Siruis\Agent\AriesRFC\feature_0048_trust_ping\Pong;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnProblemReport;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnRequest;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnResponse;
use Siruis\Agent\Connections\Endpoint;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
use Siruis\Agent\Pairwise\TheirEndpoint;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Errors\Exceptions\StateMachineTerminatedWithError;
use Siruis\Hub\Coprotocols\AbstractP2PCoProtocol;
use Siruis\Hub\Init;

/**
 * Implementation of Inviter role of the Aries connection protocol
 * See details: https://github.com/hyperledger/aries-rfcs/tree/master/features/0160-connection-protocol
 * @package Siruis\Agent\AriesRFC\feature_0160_connection_protocol\StateMachines
 */
class Inviter extends BaseConnectionStateMachine
{
    /**
     * @var string
     */
    public $connection_key;

    public function __construct(Me $me, string $connection_key, Endpoint $my_endpoint, AbstractP2PCoProtocol $coprotocol = null, int $time_to_live = 60, $logger = null)
    {
        parent::__construct($me, $my_endpoint, $coprotocol, $time_to_live, $logger);
        $this->connection_key = $connection_key;
    }

    public function create_connection(ConnRequest $request, array $did_doc = null)
    {
        $this->log(['progress' => 0, 'message' => 'Validate request', 'payload' => $request->payload, 'connection_key' => $this->connection_key]);
        try {
            $request->validate();
        } catch (SiriusValidationError $err) {
            throw new StateMachineTerminatedWithError(
                self::REQUEST_PROCESSING_ERROR,
                $err->getMessage(),
                true
            );
        }
        $this->log(['progress' => 20, 'message' => 'Request validation OK']);

        // Step 1: Extract their info from connection request
        $this->log(['progress' => 40, 'message' => 'Step 1: Extract their info from connection request']);
        $doc_uri = $request->getDocUri();
        list($their_did, $their_vk, $their_endpoint_address, $their_routing_keys) = $request->extractTheirInfo();
        $invitee_endpoint = new TheirEndpoint(
            $their_endpoint_address,
            $their_vk,
            $their_routing_keys
        );
        $co = $this->coprotocol($invitee_endpoint);
        try {
            // Step 2: build connection response
            $response = new ConnResponse(
                [],
                $this->me->did,
                $this->me->verkey,
                $this->my_endpoint->address,
                $did_doc,
                null,
                null,
                $doc_uri
            );
            if ($request->getPleaseAck()) {
                $response->setThreadId($request->getAckMessageId());
            } else {
                $response->setThreadId($request->getId());
            }
            $my_did_doc = $response->getDidDoc();
            $response->sign_connection(Init::Crypto(), $this->connection_key);

            $this->log(['progress' => 80, 'message' => 'Step-2: Connection response', 'payload' => $response->payload]);
            list($ok, $ack) = $co->switch($response);
            if ($ok) {
                if ($ack instanceof Ack || $ack instanceof Ping) {
                    // Step 3: store their did
                    $this->log(['progress' => 90, 'message' => 'Step-3: Ack received, store their DID']);
                    Init::DID()->store_their_did($their_did, $their_vk);
                    // Step 4: create pairwise
                    $their = new Their(
                        $their_did,
                        $request->getLabel(),
                        $their_endpoint_address,
                        $their_vk,
                        $their_routing_keys
                    );
                    $their_did_doc = $request->getDidDoc()->payload;
                    $metadata = [
                        'me' => [
                            'did' => $this->me->did,
                            'verkey' => $this->me->verkey,
                            'did_doc' => $my_did_doc->payload,
                        ],
                        'their' => [
                            'did' => $their_did,
                            'verkey' => $their_vk,
                            'label' => $request->getLabel(),
                            'endpoint' => [
                                'address' => $their_endpoint_address,
                                'routing_keys' => $their_routing_keys
                            ],
                            'did_doc' => $their_did_doc
                        ]
                    ];
                    $pairwise = new Pairwise($this->me, $their, $metadata);
                    $pairwise->me->did_doc = $my_did_doc;
                    $pairwise->their->did_doc = $their_did_doc;
                    if ($ack instanceof Ping) {
                        if ($ack->getResponseRequested()) {
                            $co->send(new Pong([], null, null, null, $ack->getId()));
                        }
                    }
                    $this->log(['progress' => 100, 'message' => 'Pairwise established', 'payload' => $metadata]);
                    return [true, $pairwise];
                } elseif ($ack instanceof ConnProblemReport) {
                    $this->problem_report = $ack;
                    error_log('Code: '.$ack->problemCode().' Explain: '.$ack->explain());
                    $this->log(['progress' => 100, 'message' => 'Terminated with error',
                        'problem_code' => $this->problem_report->problemCode(), 'explain' => $this->problem_report->explain()
                    ]);
                    return [false, null];
                } else {
                    throw new StateMachineTerminatedWithError(
                        self::REQUEST_PROCESSING_ERROR,
                        'Expect for connection response ack. Unexpected message type "'.(string)$response->getType().'"'
                    );
                }
            } else {
                throw new StateMachineTerminatedWithError(
                    self::REQUEST_PROCESSING_ERROR,
                    'Response ack awaiting was terminated by timeout',
                    false
                );
            }
        } catch (StateMachineTerminatedWithError $err) {
            $this->problem_report = new ConnProblemReport(
                [], null, null, null, $err->problem_code, $err->explain
            );
            if ($err->notify) {
                $co->send($this->problem_report);
            }
            $this->log([
                'progress' => 100, 'message' => 'Terminated with error',
                'problem_code' => $err->problem_code, 'explain' => $err->explain
            ]);
            return [false, null];
        }
    }
}