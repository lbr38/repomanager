<?php

namespace Controllers\Operation\Param;

use Exception;

class Description
{
    public static function check(string $description = null)
    {
        if (empty($description)) {
            return;
        }

        if (!\Controllers\Common::isAlphanumDash($description, array('.', '(', ')', '@', 'é', 'è', 'à', 'ç', 'ù', 'ê', 'ô', '+', '\'', ' '))) {
            throw new Exception('Description contains invalid characters');
        }
    }
}
