<?php

namespace Controllers\Task\Form\Param;

use Exception;
use \Controllers\Utils\Validate;

class TranslationInc
{
    public static function check(array $packageTranslation) : void
    {
        if (empty($packageTranslation)) {
            return;
        }

        foreach ($packageTranslation as $translation) {
            if (!Validate::alphaNumeric($translation)) {
                throw new Exception('Translation name contains invalid characters');
            }
        }
    }
}
