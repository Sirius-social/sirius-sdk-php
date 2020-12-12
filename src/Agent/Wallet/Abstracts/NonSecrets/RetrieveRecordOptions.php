<?php

namespace Siruis\Agent\Wallet\Abstracts\NonSecrets;

use Siruis\Base\JsonSerializable;

class RetrieveRecordOptions extends JsonSerializable
{
    public $retrieve_type;
    public $retrieve_value;
    public $retrieve_tags;

    public function __construct(
        bool $retrieve_type = false,
        bool $retrieve_value = false,
        bool $retrieve_tags = false
    ) {
        $this->retrieve_tags = $retrieve_tags;
        $this->retrieve_type = $retrieve_type;
        $this->retrieve_value = $retrieve_value;
    }

    public function checkAll()
    {
        $this->retrieve_value = true;
        $this->retrieve_type = true;
        $this->retrieve_tags = true;
    }

    public function toJson()
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

    public function serialize()
    {
        return json_encode($this->toJson());
    }

    public function deserialize($cls, $buffer)
    {
        $data = json_decode($buffer);
        $this->retrieve_type = key_exists('retrieveType', $data) ? $data['retrieveType'] : false;
        $this->retrieve_value = key_exists('retrieveValue', $data) ? $data['retrieveValue'] : false;
        $this->retrieve_tags = key_exists('retrieveTags', $data) ? $data['retrieveTags'] : false;
    }
}
