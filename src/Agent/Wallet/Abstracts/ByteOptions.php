<?php

namespace Siruis\Agent\Wallet\Abstracts;

class ByteOptions
{
    public $payload;

    public function __construct(string $payload)
    {
        $this->payload = $payload;
    }

    public function toByte()
    {
        return utf8_encode($this->payload);
    }
}
