<?php

namespace Controllers\Layout\Tab;

class Stats
{
    public static function render()
    {
        /**
         *  If the user is not an administrator and does not have permission to view repository statistics, redirect to the home page.
         */
        if (!IS_ADMIN and !in_array('view-stats', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) {
            header('Location: /');
        }

        \Controllers\Layout\Container\Render::render('stats/list');
    }
}
