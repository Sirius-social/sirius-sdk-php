<?php


namespace Siruis\Agent\AriesRFC\feature_0160_connection_protocol\Messages;


use Siruis\Agent\AriesRFC\DIDDoc;
use Siruis\Agent\AriesRFC\Utils;
use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Agent\Wallet\Abstracts\AbstractCrypto;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Messaging\Message;
use SodiumException;

class ConnProtocolMessage extends AriesProtocolMessage
{
    public $PROTOCOL = 'connections';

    public const PROTOCOL = 'connections';

    public function __construct(array $payload, ...$args)
    {
        parent::__construct($payload, ...$args);
        Message::registerMessageClass(ConnProtocolMessage::class, $this->PROTOCOL, $this->NAME);
    }

    /**
     * @param AbstractCrypto $crypto
     * @param mixed $fieldValue
     * @param string $myVerkey
     * @return array
     * @throws SodiumException
     */
    public static function signField(AbstractCrypto $crypto, $fieldValue, string $myVerkey): array
    {
        return Utils::sign($crypto, $fieldValue, $myVerkey);
    }

    /**
     * @param AbstractCrypto $crypto
     * @param array $signed_field
     * @return array|bool|mixed
     * @throws SodiumException
     */
    public static function verifySignedField(AbstractCrypto $crypto, array $signed_field)
    {
        return Utils::verify_signed($crypto, $signed_field);
    }

    public static function build_did_doc(string $did, string $verkey, string $endpoint, array $extra = null)
    {
        $key_id = $did . '#1';
        $doc = [
            '@context' => 'https://w3did.org/did/v1',
            'id' => $did,
            'authentication' => [
                [
                    'publicKey' => $key_id,
                    'type' => 'Ed25519SignatureAuthentication2018'
                ]
            ],
            'publicKey' => [[
                'id' => '1',
                'type' => 'Ed25519VerificationKey2018',
                'controller' => $did,
                'publicKeyBase58' => $verkey,
            ]],
            'service' => [[
                'id' => 'did:peer:'. $did . ';indy',
                'type' => 'IndyAgent',
                'priority' => 0,
                'recipientKeys' => [$verkey],
                'serviceEndpoint' => $endpoint
            ]],
        ];
        if ($extra) {
            array_push($doc, $extra);
        }
        return $doc;
    }

    public function getTheirDid()
    {
        return $this->payload['connection']['id'] ? $this->payload['connection']['id'] : $this->payload['connection']['DID'];
    }

    public function getDidDoc(): ?DIDDoc
    {
        $payload = $this->payload['connection']['did_doc'] ? $this->payload['connection']['did_doc'] : $this->payload['connection']['DIDDoc'];
        return $payload ? new DIDDoc($payload) : $payload;
    }

    public function getAckMessageId(): string
    {
        return $this->payload['~please_ack'] ? $this->payload['~please_ack']['message_id'] : $this->id;
    }

    public function getPleaseAck()
    {
        return $this->payload['~please_ack'] ? $this->payload['~please_ack'] : null;
    }

    public function getThreadId()
    {
        return $this->payload[self::THREAD_DECORATOR]['thid'];
    }

    public function setThreadId(string $thid)
    {
        $thread = $this->payload[self::THREAD_DECORATOR];
        $thread['thid'] = $thid;
        $this->payload[self::THREAD_DECORATOR] = $thread;
    }

    public function setPleaseAck(bool $flag)
    {
        if ($flag) {
            $this->payload['~please_ack'] = ['message_id' => $this->id];
        } elseif (key_exists('~please_ack', $this->payload)) {
            unset($this->payload['~please_ack']);
        }
    }

    public function extractTheirInfo()
    {
        $did_doc = $this->getDidDoc();
        if (!$this->getTheirDid()) {
            throw new SiriusInvalidMessageClass('Connection metadata is empty');
        }
        if (!$did_doc) {
            throw new SiriusInvalidMessageClass('DID Doc is empty');
        }
        $service = $did_doc->extractService();
        $their_endpoint = $service['serviceEndpoint'];
        $public_keys = $did_doc['publicKey'];
        $their_vk = $this->extractKey($public_keys, $service['recipientKeys'][0]);
        $routing_keys = [];
        foreach ($service['routingKeys'] as $rk) {
            array_push($routing_keys, $rk);
        }

        return [
            $did_doc,
            $their_vk,
            $their_endpoint,
            $routing_keys
        ];
    }

    protected function get_key(array $publicKeys, string $controller, string $id)
    {
        foreach ($publicKeys as $k) {
            if ($k['controller'] == $controller && $k['id'] == $id) {
                return $k['publicKeyBase58'];
            }
        }

        return null;
    }

    protected function extractKey(array $publicKeys, string $name): ?string
    {
        if (strpos($name, '#')) {
            $array = explode('#', $name);
            $controller = $array[0];
            $id = $array[1];
            return $this->get_key($publicKeys, $controller, $id);
        } else {
            return $name;
        }
    }

    public function validate()
    {
        parent::validate();
        if (key_exists('connection', $this->payload)) {
            if (!$this->getDidDoc()) {
                throw new SiriusInvalidMessageClass('DIDDoc is empty');
            }
            $this->getDidDoc()->validate();
        }
    }
}