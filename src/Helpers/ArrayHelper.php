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
}