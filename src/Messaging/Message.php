<?php


namespace Siruis\Messaging;


use ArrayObject;
use Exception;
use JsonException;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Messaging\Type\Semver;
use Siruis\Messaging\Type\Type;

class Message extends ArrayObject
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var Type|string
     */
    public $type;

    /**
     * @var Type|string
     */
    public $_type;

    /**
     * @var array
     */
    public $payload;

    /**
     * @var array
     */
    public static $msgRegistry = [];

    /**
     * Message constructor.
     * @param array $payload
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function __construct(array $payload)
    {
        parent::__construct();
        if (!key_exists('@type', $payload)) {
            throw new SiriusInvalidMessageClass('No @type in message');
        }

        if (!key_exists('@id', $payload)) {
            $payload['@id'] = self::generate_id();
        } elseif (!is_string($payload['@id'])) {
            throw new SiriusInvalidMessageClass('Message @id is invalid; must be str');
        }

        if ($this->type instanceof Type) {
            $this->_type = $payload['@type'];
            settype($payload['@type'], 'string');
        } else {
            $this->_type = Type::fromString($payload['@type']);
        }
        $this->payload = $payload;
    }

    /**
     * Serialize a message into a json string.
     *
     * @return false|string
     */
    public function serialize()
    {
        return json_encode($this->payload);
    }

    /**
     * Deserialize a message from a json string.
     *
     * @param string $serialized
     *
     * @return Message|void
     *
     * @throws SiriusInvalidMessageClass
     */
    public static function deserialize(string $serialized): Message
    {
        try {
            return new Message(json_decode($serialized));
        } catch (Exception $exception) {
            throw new SiriusInvalidMessageClass('Could not serialize message'. $exception);
        }
    }

    /**
     * Generate a message id.
     *
     * @return string
     */
    public static function generate_id()
    {
        return uniqid();
    }

    /**
     * @return Type|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Type|string $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get doc_uri from the $_type variable
     *
     * @return string
     */
    public function getDocUri()
    {
        return $this->_type->doc_uri;
    }

    /**
     * Get protocol from the $_type variable
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->_type->protocol;
    }

    /**
     * Get version from the $_type variable
     *
     * @return Type|Semver|string
     */
    public function getVersion()
    {
        return $this->_type->version;
    }

    /**
     * Get version_info from the $_type variable
     *
     * @return Semver
     */
    public function getVersionInfo()
    {
        return $this->_type->version_info;
    }

    /**
     * Get name from the $_type variable
     *
     * @return string
     */
    public function getName()
    {
        return $this->_type->name;
    }

    /**
     * Get normalized version from the $_type variable
     *
     * @return Semver
     */
    public function getNormalizedVersion()
    {
        $version_info = $this->_type->version_info;
        settype($version_info, 'string');
        return $version_info;
    }

    /**
     * @param $class
     * @param $protocol
     * @param null $name
     * 
     * @throws SiriusInvalidMessageClass
     */
    public static function registerMessageClass($class, $protocol, $name = null)
    {
        if (is_subclass_of($class, 'Siruis\Messaging\Message')) {
            $descriptor = isset(self::$msgRegistry[$protocol]) ? self::$msgRegistry[$protocol] : [];
            if ($name) {
                $descriptor[$name] = $class;
            } else {
                $descriptor['*'] = $class;
            }
            self::$msgRegistry[$protocol] = $descriptor;
        } else {
            throw new SiriusInvalidMessageClass();
        }
    }

    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws SiriusInvalidType
     */
    public static function restoreMessageInstance(array $payload)
    {
        if (key_exists('@type', $payload)) {
            $typ = Type::fromString($payload['@type']);
            $descriptor = isset(self::$msgRegistry[$typ->protocol]) ? self::$msgRegistry[$typ->protocol] : null;

            if ($descriptor) {
                if (key_exists($typ->name, $descriptor)) {
                    $cls = $descriptor[$typ->name];
                } elseif (key_exists('*', $descriptor)) {
                    $cls = $descriptor['*'];
                } else {
                    $cls = null;
                }
            } else {
                $cls = null;
            }

            if ($cls) {
                return [
                    true,
                    new $cls($payload)
                ];
            } else {
                return [
                    false,
                    null
                ];
            }
        } else {
            return [
                false,
                null
            ];
        }
    }
}