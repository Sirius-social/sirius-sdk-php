<?php


namespace Siruis\Helpers;


class ArrayHelper
{
    /**
     * Array updating helper function.
     *
     * @param array $previous
     * @param array $secondary
     * @return array
     */
    public static function update(array $previous, array $secondary): array
    {
        foreach ($secondary as $keySec => $itemSec) {
            if (key_exists($keySec, $previous)) {
                $previous[$keySec] = $itemSec;
            } else {
                array_push($previous, [$keySec => $itemSec]);
            }
        }

        return $previous;
    }

    /**
     * @param string $key
     * @param array $array
     * @param null $ret
     * @return mixed|null
     */
    public static function getValueWithKeyFromArray(string $key, array $array, $ret = null)
    {
        return key_exists($key, $array) && $array[$key] ? $array[$key] : $ret;
    }
}