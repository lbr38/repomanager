<?php

namespace Controllers\Task\Form\Param;

use Exception;
use \Controllers\Utils\Validate;

class Section
{
    public static function check(array $sections) : void
    {
        if (empty($sections)) {
            throw new Exception('Component name must be specified');
        }

        foreach ($sections as $section) {
            if (!Validate::alphaNumeric($section, ['-', '_', '.'])) {
                throw new Exception('Component name cannot contain special characters except hyphen');
            }
        }
    }
}
