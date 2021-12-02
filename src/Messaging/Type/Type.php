<?php


namespace Siruis\Messaging\Type;


use Exception;
use Siruis\Errors\Exceptions\SiriusInvalidType;

class Type
{
    public CONST MULTI_RE = '/(.*?)([a-z0-9._-]+)\/(\d[^\/]*)\/([a-z0-9._-]+)$/';
    public $doc_uri;
    public $protocol;
    public $version;
    public $version_info;
    public $name;
    public $_normalized;
    public $_str;

    /**
     * Type constructor.
     * @param string $doc_uri
     * @param string $protocol
     * @param \Siruis\Messaging\Type\Semver|string $version
     * @param string $name
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public function __construct(string $doc_uri, string $protocol, $version, string $name)
    {
        if (is_string($version)) {
            try {
                $this->version_info = Semver::fromString($version);
            } catch (Exception $exception) {
                throw new SiriusInvalidType('Invalid type version '.$version);
            }
            $this->version = $version;
        } elseif ($version instanceof Semver) {
            $this->version_info = $version;
            $version = (string)$version;
            $this->version = $version;
        } else {
            throw new SiriusInvalidType('`version` must be instance of str or Semver, got '.gettype($version));
        }

        $this->doc_uri = $doc_uri;
        $this->protocol = $protocol;
        $this->name = $name;
        $this->_str = $this->doc_uri.$this->protocol.'/'.$this->version.'/'.$this->name;
        $this->_normalized = $this->doc_uri.$this->protocol.'/'.$this->version_info.'/'.$this->name;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->_str;
    }

    /**
     * @param $type_str
     * @return \Siruis\Messaging\Type\Type
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     */
    public static function fromString($type_str): Type
    {
        if (!preg_match(self::MULTI_RE, $type_str, $matches)) {
            throw new SiriusInvalidType('Invalid message type');
        }
        return new static($matches[1], $matches[2], $matches[3], $matches[4]);
    }
}