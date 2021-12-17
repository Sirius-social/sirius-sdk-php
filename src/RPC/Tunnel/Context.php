<?php


namespace Siruis\RPC\Tunnel;


class Context
{
    /**
     * @var bool
     */
    public $encrypted;

    public function __construct()
    {
        $this->encrypted = false;
    }
}