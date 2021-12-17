<?php

namespace Siruis\Agent\Wallet\Abstracts;

use Siruis\Base\JsonSerializable;

class CacheOptions implements JsonSerializable
{
    /**
     * @var bool
     */
    public $no_cache;
    /**
     * @var bool
     */
    public $no_update;
    /**
     * @var bool
     */
    public $no_store;
    /**
     * @var int
     */
    public $min_fresh;

    /**
     * CacheOptions constructor.
     * @param bool $no_cache
     * @param bool $no_update
     * @param bool $no_store
     * @param int $min_fresh
     */
    public function __construct(
        bool $no_cache = false,
        bool $no_update = false,
        bool $no_store = false,
        int $min_fresh = 1
    ) {
        $this->no_cache = $no_cache;
        $this->no_update = $no_update;
        $this->no_store = $no_store;
        $this->min_fresh = $min_fresh;
    }

    /**
     * Get attributes like array.
     *
     * @return array
     */
    public function toJson(): array
    {
        return [
            'noCache' => $this->no_cache,
            'noUpdate' => $this->no_update,
            'noStore' => $this->no_store,
            'minFresh' => $this->min_fresh
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
     * Deserialize from the buffer.
     *
     * @param $buffer
     * @return void
     * @throws \JsonException
     */
    public function deserialize($buffer): void
    {
        $data = json_decode($buffer, false, 512, JSON_THROW_ON_ERROR);
        $this->no_cache = $this->get_attribute('noCache', $data);
        $this->no_update = $this->get_attribute('noUpdate', $data);
        $this->no_store = $this->get_attribute('noStore', $data);
        $this->min_fresh = $this->get_attribute('minFresh', $data);
    }

    /**
     * Get attribute with key from data.
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
