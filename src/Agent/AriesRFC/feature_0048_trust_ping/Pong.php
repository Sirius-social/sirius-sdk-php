<?php


namespace Siruis\Agent\AriesRFC\feature_0048_trust_ping;


use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;

class Pong extends AriesProtocolMessage
{
    public const PROTOCOL = 'trust_ping';
    public const NAME = 'ping_response';

    /**
     * @var string|null
     */
    private $comment;

    /**
     * Pong constructor.
     * @param array $payload
     * @param string|null $id_
     * @param string|null $version
     * @param string|null $doc_uri
     * @param string|null $ping_id
     * @param string|null $comment
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     */
    public function __construct(
        array $payload,
        string $id_ = null,
        string $version = null,
        string $doc_uri = null,
        string $ping_id = null,
        string $comment = null
    )
    {
        parent::__construct($payload, $id_, $version, $doc_uri);
        if (key_exists('ping_id', $payload)) {
            $ping_id = $payload['ping_id'];
        }
        if (key_exists('comment', $payload)) {
            $comment = $this->comment;
        }
        if ($ping_id) {
            $thread = $payload[self::THREAD_DECORATOR];
            $thread['thid'] = $ping_id;
            $payload[self::THREAD_DECORATOR] = $thread;
        }
        if ($comment) {
            $this->comment = $comment;
        }
        $this->payload = $payload;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @return string|null
     */
    public function getPingId(): ?string
    {
        return $this->payload[self::THREAD_DECORATOR]['thid'] ?
            $this->payload[self::THREAD_DECORATOR]['thid'] :
            null;
    }
}