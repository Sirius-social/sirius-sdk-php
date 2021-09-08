<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages;


use Siruis\Messaging\Message;
use Siruis\Messaging\Validators;

class ConnRequest extends ConnProtocolMessage
{
    public $NAME = 'request';

    public function __construct(array $payload,
                                string $label = null,
                                string $did = null,
                                string $verkey = null,
                                string $endpoint = null,
                                array $did_doc_extra = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $id_, $version, $doc_uri);
        if ($label) {
            $this->payload['label'] = $label;
        }
        if ($did && $verkey && $endpoint) {
            $extra = $did_doc_extra ? $did_doc_extra : [];
            $this->payload['connection'] = [
                'DID' => $did,
                'DIDDoc' => self::build_did_doc($did, $verkey, $endpoint, $extra),
            ];
        }
        Message::registerMessageClass(ConnRequest::class, $this->PROTOCOL, $this->NAME);
    }

    public function getLabel()
    {
        return $this->payload['label'] ? $this->payload['label'] : null;
    }

    public function validate()
    {
        parent::validate();
        Validators::check_for_attributes($this->payload, ['label', 'connection']);
    }
}