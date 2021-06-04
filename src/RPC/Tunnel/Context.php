<?php


namespace Siruis\RPC\Tunnel;


class Context
{
    public $encrypted;

    public function __construct()
    {
        $this->encrypted = false;
    }
}