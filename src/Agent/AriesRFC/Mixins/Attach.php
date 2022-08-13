<?php


namespace Siruis\Agent\AriesRFC\Mixins;


use ArrayObject;

class Attach extends ArrayObject
{
    public $payload;

    /**
     * @throws \SodiumException
     */
    public function __construct(
        string $id = null,
        string $mime_type = null,
        string $filename = null,
        string $lastmod_time = null,
        string $description = null,
        string $data = null,
        ...$args)
    {
        parent::__construct(...$args);
        if ($id) {
            $this->payload['@id'] = $id;
        }
        if ($mime_type) {
            $this->payload['mime-type'] = $mime_type;
        }
        if ($filename) {
            $this->payload['filename'] = $filename;
        }
        if ($lastmod_time) {
            $this->payload['lastmod_time'] = $lastmod_time;
        }
        if ($description) {
            $this->payload['description'] = $description;
        }
        if ($data) {
            $this->payload['data'] = [
                'base64' => bytes_to_b64($data)
            ];
        }
    }

    public function getId()
    {
        return $this->payload['@id'];
    }

    public function getMimeType()
    {
        return $this->payload['mime-type'];
    }

    public function getFilename()
    {
        return $this->payload['filename'];
    }

    public function getLastmodTime()
    {
        return $this->payload['lastmod_time'];
    }

    public function getDescription()
    {
        return $this->payload['description'];
    }

    /**
     * @throws \SodiumException
     */
    public function getData(): string
    {
        return b64_to_bytes($this->payload['data']['base64']);
    }
}