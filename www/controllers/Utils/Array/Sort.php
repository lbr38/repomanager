<?php

namespace Controllers\Utils\Array;

class Sort
{
    /**
     *  Sort an array by specified key value
     */
    public static function byKey(string $key, array $data) : array
    {
        $result = [];

        foreach ($data as $val) {
            if (array_key_exists($key, $val)) {
                $result[$val[$key]][] = $val;
            } else {
                $result[''][] = $val;
            }
        }

        return $result;
    }
}
