<?php


namespace Siruis\Base;


interface JsonSerializable
{
    public function serialize();

    /**
     * @param array|string $buffer
     * @return mixed
     */
    public function deserialize($buffer);
}