<?php

namespace Siruis\Agent\Wallet\Abstracts;

use Siruis\Base\JsonSerializable;

class CacheOptions implements JsonSerializable
{
    public $no_cache;
    public $no_update;
    public $no_store;
    public $min_fresh;

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

    public function toJson()
    {
        return [
            'noCache' => $this->no_cache,
            'noUpdate' => $this->no_update,
            'noStore' => $this->no_store,
            'minFresh' => $this->min_fresh
        ];
    }

    public function serialize()
    {
        return json_encode($this->toJson());
    }

    public function deserialize($buffer)
    {
        $data = json_decode($buffer);
        $this->no_cache = key_exists('noCache', $data) ? $data['noCache'] : false;
        $this->no_update = key_exists('noUpdate', $data) ? $data['noUpdate'] : false;
        $this->no_store = key_exists('noStore', $data) ? $data['noStore'] : false;
        $this->min_fresh = key_exists('minFresh', $data) ? $data['minFresh'] : false;
    }
}
