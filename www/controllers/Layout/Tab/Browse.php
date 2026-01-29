<?php
namespace Controllers\Layout\Tab;

use \Controllers\User\Permission\Repo as RepoPermission;
use \Controllers\Layout\Container\Render;

class Browse
{
    public static function render()
    {
        // If the user is not an administrator and does not have permission to browse repository, redirect to the home page
        if (!RepoPermission::allowedAction('browse')) {
            header('Location: /');
        }

        Render::render('browse/list');
        Render::render('browse/actions');
    }
}
