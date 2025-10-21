<?php

namespace Controllers\App\Structure;

class File
{
    /**
     *  Create app files if not exist
     */
    public static function create()
    {
        $__CREATE_FILES_ERROR = 0;
        $__CREATE_FILES_MESSAGES = [];

        /**
         *  Generate GPG key and configuration if not exists
         */
        try {
            $mygpg = new \Controllers\Gpg();
            $mygpg->init();
        } catch (\Exception $e) {
            $__CREATE_FILES_ERROR++;
            $__CREATE_FILES_MESSAGES[] = $e->getMessage();
        }

        if (!defined('__CREATE_FILES_ERROR')) {
            define('__CREATE_FILES_ERROR', $__CREATE_FILES_ERROR);
        }
        if (!defined('__CREATE_FILES_MESSAGES')) {
            define('__CREATE_FILES_MESSAGES', $__CREATE_FILES_MESSAGES);
        }

        unset($__CREATE_FILES_ERROR, $__CREATE_FILES_MESSAGES, $mygpg);
    }
}
