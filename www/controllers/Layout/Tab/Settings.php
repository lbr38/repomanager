<?php

namespace Controllers\Layout\Tab;

class Settings
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

        \Controllers\Layout\Container\Render::render('settings/settings');
        \Controllers\Layout\Container\Render::render('settings/right-section');
    }
}
