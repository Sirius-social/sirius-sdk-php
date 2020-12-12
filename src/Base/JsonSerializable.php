<?php


namespace Siruis\Base;


abstract class JsonSerializable
{
    abstract public function serialize();

    /**
     * @param $cls
     * @param array|string $buffer
     * @return mixed
     */
    abstract public function deserialize($cls, $buffer);
}