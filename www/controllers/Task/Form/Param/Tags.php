<?php

namespace Controllers\Task\Form\Param;

use Exception;
use Controllers\Utils\Validate;

class Tags
{
    public static function check(array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        foreach ($tags as $tag) {
            if (!Validate::alphaNumericHyphen($tag, ['.', '+'])) {
                throw new Exception('List of tags contains invalid characters');
            }
        }
    }
}
