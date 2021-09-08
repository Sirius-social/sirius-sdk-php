<?php


namespace Siruis\Agent\AriesRFC\feature_0048_trust_ping;


use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Messaging\Message;

class Ping extends AriesProtocolMessage
{
    public $PROTOCOL = 'trust_ping';
    public $NAME = 'ping';

    public const PROTOCOL = 'trust_ping';
    public const NAME = 'ping';

    /**
     * @var bool|null
     */
    private $response_requested;

    /**
     * Ping constructor.
     * @param array $payload
     * @param string|null $id_
     * @param string|null $version
     * @param string|null $doc_uri
     * @param string|null $comment
     * @param bool|null $response_requested
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     */
    public function __construct(
        array $payload,
        string $id_ = null,
        string $version = null,
        string $doc_uri = null,
        string $comment = null,
        bool $response_requested = null
    )
    {
        parent::__construct($payload, $id_, $version, $doc_uri);
        if (key_exists('comment', $payload)) {
            $this->payload['comment'] = $payload['comment'];
        } elseif ($comment) {
            $this->payload['comment'] = $comment;
        }
        if (key_exists('response_requested', $payload)) {
            $this->response_requested = $payload['response_requested'];
        } elseif ($response_requested) {
            $this->response_requested = $response_requested;
        }
        Message::registerMessageClass(Ping::class, $this->PROTOCOL, $this->NAME);
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->payload['comment'];
    }

    /**
     * @return bool|null
     */
    public function getResponseRequested(): ?bool
    {
        return $this->response_requested;
    }

    /**
     * @param bool $response_requested
     */
    public function setResponseRequested(bool $response_requested): void
    {
        $this->response_requested = $response_requested;
    }
}