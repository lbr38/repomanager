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

    /**
     *  Function to rebuild a $_FILES[] array which is quite badly done and therefore complicated to browse
     *  https://www.php.net/manual/fr/features.file-upload.multiple.php
     */
    public static function byPostFiles($files): array
    {
        $array = [];

        for ($i = 0; $i < count($files['name']); $i++) {
            foreach (array_keys($files) as $key) {
                $array[$i][$key] = $files[$key][$i];
            }
        }

        return $array;
    }
}
