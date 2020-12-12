<?php

namespace Siruis\Agent\Wallet\Abstracts;

use Siruis\Base\JsonSerializable;

class PurgeOptions extends JsonSerializable
{
    public $max_age;

    public function __construct(int $max_age = 1)
    {
        $this->max_age = $max_age;
    }

    public function toJson()
    {
        return [
            'maxAge' => $this->max_age
        ];
    }

    public function serialize()
    {
        return json_encode($this->toJson());
    }

    public function deserialize($cls, $buffer)
    {
        $data = json_decode($buffer);
        $this->max_age = key_exists('maxAge', $data) ? $data['maxAge'] : -1;
    }
}
