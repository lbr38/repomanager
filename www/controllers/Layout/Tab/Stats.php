<?php

namespace Controllers\Layout\Tab;

use \Controllers\User\Permission\Repo as RepoPermission;
use \Controllers\Layout\Container\Render;

class Stats
{
    public static function render()
    {
        // If the user does not have permission to view repository statistics, redirect to the home page
        if (!RepoPermission::allowedAction('view-stats')) {
            header('Location: /');
        }

        if (__ACTUAL_URI__[2] == 'repo') {
            Render::render('stats/repo');
        }
    }
}
