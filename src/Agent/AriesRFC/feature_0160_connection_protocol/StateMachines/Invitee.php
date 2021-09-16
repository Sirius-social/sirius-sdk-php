<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol\StateMachines;


use Siruis\Agent\AriesRFC\feature_0015_acks\Ack;
use Siruis\Agent\AriesRFC\feature_0015_acks\Status;
use Siruis\Agent\AriesRFC\feature_0048_trust_ping\Ping;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnProblemReport;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnRequest;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\ConnResponse;
use Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages\Invitation;
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
 * Implementation of Invitee role of the Aries connection protocol
 * See details: https://github.com/hyperledger/aries-rfcs/tree/master/features/0160-connection-protocol
 * @package Siruis\Agent\AriesRFC\feature_0160_connection_protocol\StateMachines
 */
class Invitee extends BaseConnectionStateMachine
{
    public function __construct(Me $me,
                                Endpoint $my_endpoint,
                                AbstractP2PCoProtocol $coprotocol = null,
                                int $time_to_live = 60,
                                $logger = null)
    {
        parent::__construct($me, $my_endpoint, $coprotocol, $time_to_live, $logger);
    }

    public function create_connection(Invitation $invitation, string $my_label, array $did_doc = null)
    {
        // Validation invitation
        $this->log(['progress' => 0, 'message' => 'Invitation validate', 'payload' => $invitation->payload]);
        try {
            $invitation->validate();
        } catch (SiriusValidationError $err) {
            throw new StateMachineTerminatedWithError(
                self::REQUEST_PROCESSING_ERROR,
                'Invitation error: '.$err->getMessage(),
                true
            );
        }
        $this->log(['progress' => 20, 'message' => 'Invitation validation OK']);

        $doc_uri = $invitation->getDocUri();
        // Extract Inviter connection key
        $connection_key = $invitation->getRecipientKeys()[0];
        $inviter_endpoint = new TheirEndpoint($invitation->getEndpoint(), $connection_key);
        $co = $this->coprotocol($inviter_endpoint);
        $this->log(['progress' => 40, 'message' => 'Transport channel is allocated']);
        try {
            $request = new ConnRequest(
                [],
                $my_label,
                $this->me->did,
                $this->me->verkey,
                $this->my_endpoint->address,
                $did_doc,
                null,
                null,
                $doc_uri
            );

            $this->log(['progress' => 50, 'message' => 'Step-1: send connection request to Inviter', 'payload' => $request->payload]);
            list($ok, $response) = $co->switch($request);
            if ($ok) {
                if ($response instanceof ConnResponse) {
                    // Step 2: process connection response from Inviter
                    $this->log(['progress' => 60, 'message' => 'Step-2: process connection response from Inviter', 'payload' => $request->payload]);
                    $success = $response->verify_connection(Init::Crypto());
                    try {
                        $response->validate();
                    } catch (SiriusValidationError $err) {
                        throw new StateMachineTerminatedWithError(
                            self::RESPONSE_NOT_ACCEPTED,
                            $err->getMessage(),
                            true
                        );
                    }
                    if ($success && $response->payload['connection~sig']['signer'] == $connection_key) {
                        // Step 3: extract Inviter info and store did
                        $this->log(['progress' => 70, 'message' => 'Step-3: extract Inviter info and store DID']);
                        list($their_did, $their_vk, $their_endpoint_address, $their_routing_keys) = $response->extractTheirInfo();
                        Init::DID()->store_their_did($their_did, $their_vk);

                        $actual_endpoint = new TheirEndpoint(
                            $their_endpoint_address,
                            $their_vk
                        );
                        $co = $this->coprotocol($actual_endpoint);
                        // Step 4: Send ack to Inviter
                        if ($response->getPleaseAck()) {
                            $ack = new Ack([], null, null,  null, $response->getAckMessageId(), Status::OK);
                            $co->send($ack);
                            $this->log(['progress' => 90, 'message' => 'Step-4: Send ack to Inviter']);
                        } else {
                            $ping = new Ping([], null,  null, null, 'Connection established', false);
                            $co->send($ping);
                            $this->log(['progress' => 90, 'message' => 'Step-4: Send ping to Inviter']);
                        }
                        // Step 5: Make Pairwise instance
                        $their = new Their(
                            $their_did,
                            $invitation->getLabel(),
                            $their_endpoint_address,
                            $their_vk,
                            $their_routing_keys
                        );
                        $my_did_doc = (array)$request->getDidDoc();
                        $their_did_doc = (array)$response->getDidDoc();
                        $metadata = [
                            'me' => [
                                'did' => $this->me->did,
                                'verkey' => $this->me->verkey,
                                'did_doc' => $my_did_doc
                            ],
                            'their' => [
                                'did' => $their_did,
                                'verkey' => $their_vk,
                                'label' => $invitation->label,
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
                        $this->log(['progress' => 100, 'message' => 'Pairwise established', 'payload' => $metadata]);
                        return [true, $pairwise];
                    } else {
                        throw new StateMachineTerminatedWithError(
                            self::RESPONSE_NOT_ACCEPTED,
                            'Invalid connection response signature for connection_key: "'.$connection_key.'"'
                        );
                    }
                } elseif ($response instanceof ConnProblemReport) {
                    $this->problem_report = $response;
                    error_log('Code: '.$response->problemCode().' Explain: '.$response->explain());
                    $this->log(['progress' => 100, 'message' => 'Terminated with error',
                        'problem_code' => $this->problem_report->problemCode(), 'explain' => $this->problem_report->explain()
                    ]);
                    return [false, null];
                }
            } else {
                throw new StateMachineTerminatedWithError(
                    self::RESPONSE_PROCESSING_ERROR,
                    'Response awaiting was terminated by timeout',
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