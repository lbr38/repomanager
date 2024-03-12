<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Section
{
    public static function check(string $section)
    {
        if (empty($section)) {
            throw new Exception('Section name must be specified');
        }

        if (!\Controllers\Common::isAlphanum($section, array('-', '_', '.'))) {
            throw new Exception('Section name cannot contain special characters except hyphen');
        }
    }
}
