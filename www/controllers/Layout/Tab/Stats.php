<?php

namespace Controllers\Layout\Tab;

use \Controllers\User\Permission\Repo as RepoPermission;

class Stats
{
    public static function render()
    {
        /**
         *  If the user does not have permission to view repository statistics, redirect to the home page.
         */
        if (!RepoPermission::allowedAction('view-stats')) {
            header('Location: /');
        }

        \Controllers\Layout\Container\Render::render('stats/list');
    }
}
