<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Section
{
    public static function check(array $sections) : void
    {
        if (empty($sections)) {
            throw new Exception('Section name must be specified');
        }

        foreach ($sections as $section) {
            if (!\Controllers\Common::isAlphanum($section, array('-', '_', '.'))) {
                throw new Exception('Section name cannot contain special characters except hyphen');
            }
        }
    }
}
