<?php

namespace Siruis\Agent\Wallet\Abstracts\NonSecrets;

use Siruis\Base\JsonSerializable;

class RetrieveRecordOptions implements JsonSerializable
{
    /**
     * @var bool
     */
    public $retrieve_type;
    /**
     * @var bool
     */
    public $retrieve_value;
    /**
     * @var bool
     */
    public $retrieve_tags;

    /**
     * RetrieveRecordOptions constructor.
     *
     * @param bool $retrieve_type
     * @param bool $retrieve_value
     * @param bool $retrieve_tags
     */
    public function __construct(
        bool $retrieve_type = false,
        bool $retrieve_value = false,
        bool $retrieve_tags = false
    )
    {
        $this->retrieve_tags = $retrieve_tags;
        $this->retrieve_type = $retrieve_type;
        $this->retrieve_value = $retrieve_value;
    }

    /**
     * Set all attributes true.
     *
     * @return void
     */
    public function checkAll(): void
    {
        $this->retrieve_value = true;
        $this->retrieve_type = true;
        $this->retrieve_tags = true;
    }

    /**
     * Get all data like array.
     *
     * @return array
     */
    public function toJson(): array
    {
        $options = [];
        if ($this->retrieve_type) {
            $options['retrieveType'] = $this->retrieve_type;
        }
        if ($this->retrieve_value) {
            $options['retrieveValue'] = $this->retrieve_value;
        }
        if ($this->retrieve_tags) {
            $options['retrieveTags'] = $this->retrieve_tags;
        }
        return $options;
    }

    /**
     * Serialize attributes.
     *
     * @return false|string
     * @throws \JsonException
     */
    public function serialize()
    {
        return json_encode($this->toJson(), JSON_THROW_ON_ERROR);
    }

    /**
     * Deserialize from the given buffer.
     *
     * @param $buffer
     * @return void
     * @throws \JsonException
     */
    public function deserialize($buffer): void
    {
        $data = json_decode($buffer, false, 512, JSON_THROW_ON_ERROR);
        $this->retrieve_type = $this->get_attribute('retrieveType', $data);
        $this->retrieve_value = $this->get_attribute('retrieveValue', $data);
        $this->retrieve_tags = $this->get_attribute('retrieveTags', $data);
    }

    /**
     * Get attribute with key from the array.
     *
     * @param string $key
     * @param array $data
     * @return false|mixed
     */
    protected function get_attribute(string $key, array $data)
    {
        return array_key_exists($key, $data) ? $data[$key] : false;
    }

    public static function unserialize($buffer): void
    {
        // You are never use this method.
    }
}
