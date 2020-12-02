<?php


namespace Siruis\Agent\Base;


use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;
use Siruis\Messaging\Validators;

class AriesProtocolMessage extends Message
{
    public const VALID_DOC_URI = ['https://didcomm.org/', 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/'];
    public const ARIES_DOC_URI = self::VALID_DOC_URI[1];
    /**
     * @var string|array
     */
    public const THREAD_DECORATOR = '~thread';

    public const DOC_URI = self::ARIES_DOC_URI;
    public const PROTOCOL = null;
    public const NAME = null;
    public const DEF_VERSION = '1.0';
    /**
     * @var string|null
     */
    private $id_;
    /**
     * @var string|null
     */
    private $version;
    /**
     * @var string|null
     */
    private $doc_uri;
    /**
     * @var string|null
     */
    private $protocol;
    /**
     * @var string|null
     */
    private $name;

    /**
     * AriesProtocolMessage constructor.
     * @param array $payload
     * @param string|null $id_
     * @param string|null $version
     * @param string|null $doc_uri
     * @throws SiriusValidationError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function __construct(
        array $payload,
        string $id_ = null,
        string $version = null,
        string $doc_uri = null
    )
    {
        if (self::NAME && !key_exists('@type', $payload)) {
            $payload['@type'] = (string) new Type(
                $doc_uri,
                self::PROTOCOL,
                self::NAME,
                $version ? $version : self::DEF_VERSION
            );
        }
        parent::__construct($payload);
        $this->payload = $payload;
        $this->id_ = $id_;
        $this->version = $version;
        $this->doc_uri = $doc_uri;
        if ($this->id_) {
            $payload['@id'] = $this->id_;
        }
        if ($this->doc_uri && !in_array($this->doc_uri, self::VALID_DOC_URI)) {
            throw new SiriusValidationError('Unexpected doc_uri "'.$this->doc_uri.'"');
        }
        if ($this->protocol && $this->protocol != self::PROTOCOL) {
            throw new SiriusValidationError('Unexpected protocol "'.$this->protocol.'"');
        }
        if ($this->name != self::NAME) {
            throw new SiriusValidationError('Unexpected name "'.$this->name.'"');
        }
    }

    /**
     * @throws SiriusValidationError
     */
    public function validate()
    {
        (new Validators)->validate_common_blocks($this->payload);
    }
}