<?php


namespace Siruis\Base;


abstract class JsonSerializable
{
    abstract public static function serialize();

    /**
     * @param $cls
     * @param array|string $buffer
     * @return mixed
     */
    abstract public static function deserialize($cls, $buffer);
}