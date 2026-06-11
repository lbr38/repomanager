<?php

namespace Controllers\Api;

class Serializer
{
    /**
     *  Recursively normalize the keys of an array to lowercase
     *  so that the API always returns a consistent key format, no matter
     *  whether the data comes from the database (PascalCase columns) or
     *  is built by the backend (lowercase keys).
     */
    public static function normalize($data)
    {
        // Only arrays have keys to normalize, return scalars as-is
        if (!is_array($data)) {
            return $data;
        }

        $result = [];

        foreach ($data as $key => $value) {
            // Recursively normalize nested arrays
            $value = self::normalize($value);

            // Numeric keys (sequential lists) are kept as-is
            if (is_int($key)) {
                $result[$key] = $value;
                continue;
            }

            $result[strtolower($key)] = $value;
        }

        return $result;
    }
}
