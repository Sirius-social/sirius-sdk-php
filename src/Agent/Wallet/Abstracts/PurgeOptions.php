<?php

namespace Siruis\Agent\Wallet\Abstracts;

use Siruis\Base\JsonSerializable;

class PurgeOptions implements JsonSerializable
{
    /**
     * @var int
     */
    public $max_age;

    /**
     * PurgeOptions constructor.
     * @param int $max_age
     */
    public function __construct(int $max_age = 1)
    {
        $this->max_age = $max_age;
    }

    /**
     * Get attrubtes like array.
     *
     * @return int[]
     */
    public function toJson(): array
    {
        return [
            'maxAge' => $this->max_age
        ];
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
        $this->max_age = array_key_exists('maxAge', $data) ? $data['maxAge'] : -1;
    }

    public static function unserialize($buffer): void
    {
        // You are never use this method.
    }
}
