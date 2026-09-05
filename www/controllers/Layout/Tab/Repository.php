<?php

namespace Controllers\Layout\Tab;

use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\Layout\Container\Render;

class Repository
{
    public static function render()
    {
        // If the user does not have permission to view repository statistics, redirect to the home page
        if (!RepoPermission::allowedAction('view-stats')) {
            header('Location: /');
        }

        Render::render('repo/kpi');
        Render::render('repo/stats');
    }
}
