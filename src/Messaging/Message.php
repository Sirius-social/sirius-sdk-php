<?php


namespace Siruis\Messaging;


use ArrayObject;
use Exception;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
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
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public function __construct(array $payload)
    {
        parent::__construct();
        if (!array_key_exists('@type', $payload)) {
            throw new SiriusInvalidMessageClass('No @type in message');
        }
        if (!array_key_exists('@id', $payload)) {
            $payload['@id'] = self::generate_id();
            $this->id = $payload['@id'];
        } elseif (!is_string($payload['@id'])) {
            throw new SiriusInvalidMessageClass('Message @id is invalid; must be str');
        } else {
            $this->id = $payload['@id'];
        }

        if ($this->type instanceof Type) {
            $this->_type = $payload['@type'];
            $payload['@type'] = (string)$this->_type;
        } else {
            $this->_type = Type::fromString($payload['@type']);
        }
        $this->payload = $payload;
        $this->type = $payload['@type'];
    }

    /**
     * @param string $key
     * @return false|mixed
     */
    public function getAttribute(string $key)
    {
        if (array_key_exists($key, $this->payload)) {
            return $this->payload[$key];
        }

        return false;
    }

    /**
     * Serialize a message into a json string.
     *
     * @return false|string
     * @throws \JsonException
     */
    public function serialize()
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Deserialize a message from a json string.
     *
     * @param string $serialized
     * @return \Siruis\Messaging\Message
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     */
    public static function deserialize(string $serialized): Message
    {
        try {
            return new Message(json_decode($serialized, true, 512, JSON_THROW_ON_ERROR));
        } catch (Exception $exception) {
            throw new SiriusInvalidMessageClass('Could not serialize message'. $exception);
        }
    }

    /**
     * Generate a message id.
     *
     * @return string
     */
    public static function generate_id(): string
    {
        return uniqid('', true);
    }

    /**
     * @return mixed|\Siruis\Messaging\Type\Type|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \Siruis\Messaging\Type\Type|string $type
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
    public function getDocUri(): string
    {
        return $this->_type->doc_uri;
    }

    /**
     * Get protocol from the $_type variable
     *
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->_type->protocol;
    }

    /**
     * Get version from the $_type variable
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->_type->version;
    }

    /**
     * Get version_info from the $_type variable
     *
     * @return \Siruis\Messaging\Type\Semver
     */
    public function getVersionInfo(): Semver
    {
        return $this->_type->version_info;
    }

    /**
     * Get name from the $_type variable
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_type->name;
    }

    /**
     * Get normalized version from the $_type variable
     *
     * @return string
     */
    public function getNormalizedVersion(): string
    {
        $version_info = $this->_type->version_info;
        return (string)$version_info;
    }

    /**
     * @param $class
     * @param $protocol
     * @param null $name
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     */
    public static function registerMessageClass($class, $protocol, $name = null): void
    {
        if (is_subclass_of($class, self::class)) {
            $descriptor = self::$msgRegistry[$protocol] ?? [];
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
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public static function restoreMessageInstance(array $payload): array
    {
        if (array_key_exists('@type', $payload)) {
            $typ = Type::fromString($payload['@type']);
            $descriptor = self::$msgRegistry[$typ->protocol] ?? null;

            if ($descriptor) {
                if (array_key_exists($typ->name, $descriptor)) {
                    $cls = $descriptor[$typ->name];
                } elseif (array_key_exists('*', $descriptor)) {
                    $cls = $descriptor['*'];
                } else {
                    $cls = null;
                }
            } else {
                $cls = null;
            }

            if ($cls) {
                return [true, new $cls($payload)];
            }

            return [false, null];
        }

        return [false, null];
    }

    /**
     * Dynamically retrieve attributes on the message.
     *
     * @param $name
     * @return false|mixed
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param $name
     * @param $value
     * @return void
     */
    public function __set($name, $value): void
    {
        $this->payload[$name] = $value;
    }

    /**
     * Determine if an attribute exists on the message.
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->payload[$name]);
    }
}