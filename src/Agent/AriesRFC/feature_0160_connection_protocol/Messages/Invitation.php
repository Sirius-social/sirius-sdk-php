<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages;


use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Messaging\Message;
use Siruis\Messaging\Validators;
use SodiumException;

class Invitation extends ConnProtocolMessage
{
    public $NAME = 'invitation';

    public function __construct(array $payload,
                                string $label = null,
                                array $recipient_keys = null,
                                string $endpoint = null,
                                array $routing_keys = null,
                                string $did = null,
                                string $id_ = null,
                                string $version = null,
                                string $doc_uri = null)
    {
        parent::__construct($payload, $id_, $version, $doc_uri);
        if ($label) {
            $this->payload['label'] = $label;
        }
        if ($recipient_keys) {
            $this->payload['recipientKeys'] = $recipient_keys;
        }
        if ($endpoint) {
            $this->payload['serviceEndpoint'] = $endpoint;
        }
        if ($routing_keys) {
            $this->payload['routingKeys'] = $routing_keys;
        }
        if ($did) {
            $this->payload['did'] = $did;
        }
        Message::registerMessageClass(Invitation::class, $this->PROTOCOL, $this->NAME);
    }

    public function validate()
    {
        Validators::check_for_attributes($this->payload, [
            'label', 'recipientKeys', 'serviceEndpoint'
        ]);
    }

    /**
     * @param string $url
     * @return Invitation
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     * @throws SodiumException
     */
    public static function fromUrl(string $url)
    {
        preg_match('/(.+)?c_i=(.+)/', $url, $matches);
        if (!$matches)  {
            throw new SiriusInvalidMessageClass('Invite string is improperly formatted');
        }
        $msg = Message::deserialize(Encryption::b64_to_bytes(mb_convert_encoding($matches[2], 'utf-8'), true));
        if ($msg->getProtocol() != self::PROTOCOL) {
            throw new SiriusInvalidMessageClass('Unexpected protocol ' . $msg->type->protocol);
        }
        if ($msg->getName() != self::NAME) {
            throw new SiriusInvalidMessageClass('Unexpected protocol name ' . $msg->type->name);
        }
        $msgArray = json_decode($msg->serialize());
        $label = $msgArray['label'];
        unset($msgArray['label']);
        if (!$label) {
            throw new SiriusInvalidMessageClass('label attribute missing');
        }
        $recipient_keys = $msgArray['recipientKeys'];
        unset($msgArray['recipientKeys']);
        if (!$recipient_keys) {
            throw new SiriusInvalidMessageClass('recipientKeys attribute missing');
        }
        $endpoint = $msgArray['serviceEndpoint'];
        unset($msgArray['serviceEndpoint']);
        if (!$endpoint) {
            throw new SiriusInvalidMessageClass('serviceEndpoint attribute missing');
        }
        $routing_keys = $msgArray['routingKeys'] ? $msgArray['routingKeys'] : [];
        unset($msgArray['routingKeys']);
        return new self($msgArray, $label, $recipient_keys, $endpoint, $routing_keys);
    }

    public function getInvitationUrl()
    {
        $b64_invite = Encryption::bytes_to_b64($this->serialize(), true);
        return '?c_i=' . $b64_invite;
    }

    public function getLabel()
    {
        return $this->payload['label'] ? $this->payload['label'] : null;
    }

    public function getRecipientKeys()
    {
        return $this->payload['recipientKeys'] ? $this->payload['recipientKeys'] : null;
    }

    public function getEndpoint()
    {
        return $this->payload['serviceEndpoint'] ? $this->payload['serviceEndpoint'] : null;
    }

    public function getRoutingKeys()
    {
        return $this->payload['routingKeys'] ? $this->payload['routingKeys'] : [];
    }
}