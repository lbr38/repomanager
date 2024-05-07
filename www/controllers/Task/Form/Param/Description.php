<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Description
{
    public static function check(string $description = null) : void
    {
        if (empty($description)) {
            return;
        }

        if (!\Controllers\Common::isAlphanumDash($description, array('.', '(', ')', '@', 'é', 'è', 'à', 'ç', 'ù', 'ê', 'ô', '+', '\'', ' '))) {
            throw new Exception('Description contains invalid characters');
        }
    }
}
