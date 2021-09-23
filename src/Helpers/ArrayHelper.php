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

    /**
     * @param array $value
     * @return bool
     */
    public static function is_assoc(array $value)
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * @param array $value
     * @param array $keys
     * @return bool
     */
    public static function all_keys_exists(array $value, array $keys)
    {
        $success_keys = [];
        foreach ($keys as $key) {
            if (in_array($key, array_keys($value))) {
                array_push($key);
            }
        }
        return count($success_keys) == count($keys);
    }
}