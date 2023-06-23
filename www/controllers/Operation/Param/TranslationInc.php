<?php

namespace Controllers\Operation\Param;

use Exception;

class TranslationInc
{
    public static function check(array $packageTranslation)
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
