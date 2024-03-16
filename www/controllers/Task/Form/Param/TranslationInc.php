<?php

namespace Controllers\Task\Form\Param;

use Exception;

class TranslationInc
{
    public static function check(array $packageTranslation) : void
    {
        if (empty($packageTranslation)) {
            return;
        }

        foreach ($packageTranslation as $translation) {
            if (!\Controllers\Common::isAlphanum($translation)) {
                throw new Exception('Translation name contains invalid characters');
            }
        }
    }
}
