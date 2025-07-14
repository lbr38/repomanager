<?php

namespace Controllers\Layout\Tab;

class Browse
{
    public static function render()
    {
        /**
         *  If the user is not an administrator and does not have permission to browse repository, redirect to the home page.
         */
        if (!IS_ADMIN and !in_array('browse', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) {
            header('Location: /');
        }

        \Controllers\Layout\Container\Render::render('browse/list');
        \Controllers\Layout\Container\Render::render('browse/actions');
    }
}
