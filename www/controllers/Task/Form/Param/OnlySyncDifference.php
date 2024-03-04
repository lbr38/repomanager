<?php

namespace Controllers\Task\Form\Param;

use Exception;

class OnlySyncDifference
{
    public static function check(string $onlySyncTheDifference) : void
    {
        if ($onlySyncTheDifference != 'true' and $onlySyncTheDifference != 'false') {
            throw new Exception('Only sync the difference parameter is invalid');
        }
    }
}
