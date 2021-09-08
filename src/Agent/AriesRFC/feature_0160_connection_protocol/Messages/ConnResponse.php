<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages;


use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Messaging\Message;
use Siruis\Messaging\Validators;

class ConnResponse extends ConnProtocolMessage
{
    public $NAME = 'response';

    public function __construct(array $payload,
                                string $did = null,
                                string $verkey = null,
                                string $endpoint = null,
                                array $did_doc_extra = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $id_, $version, $doc_uri);
        if ($did && $verkey && $endpoint) {
            $extra = $did_doc_extra ? $did_doc_extra : [];
            $this->payload['connection'] = [
                'DID' => $did,
                'DIDDoc' => self::build_did_doc($did, $verkey, $endpoint, $extra),
            ];
        }
        Message::registerMessageClass(ConnResponse::class, $this->PROTOCOL, $this->NAME);
    }

    public function validate()
    {
        parent::validate();
        Validators::check_for_attributes($this->payload, ['connection~sig']);
    }

    public function sign_connection(AbstractCrypto $crypto, string $key)
    {
        $this->payload['connection~sig'] = self::signField($crypto, $this->payload['connection'], $key);
        unset($this->payload['connection']);
    }

    public function verify_connection(AbstractCrypto $crypto)
    {
        $verifiedArray = self::verifySignedField($crypto, $this->payload['connection~sig']);
        $connection = $verifiedArray[0];
        $success = $verifiedArray[1];
        if ($success) {
            $this->payload['connection'] = $connection;
        }
        return $success;
    }
}