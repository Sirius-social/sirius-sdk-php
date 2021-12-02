<?php

namespace Siruis\RPC;

class RawBytes
{
    public $payload;

    public function __construct(string $payload)
    {
        $this->payload = $payload;
    }

    public function toBytes(): string
    {
        return utf8_encode($this->payload);
    }
}
