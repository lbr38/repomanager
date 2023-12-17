<?php

namespace Controllers\Layout\Tab;

class Profiles
{
    public static function render()
    {
        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        include_once(ROOT . '/views/profiles.template.php');
    }
}
