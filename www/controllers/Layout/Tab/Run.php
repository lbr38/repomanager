<?php

namespace Controllers\Layout\Tab;

use \Controllers\User\Permission\Task as TaskPermission;
use \Controllers\Layout\Container\Render;

class Run
{
    public static function render()
    {
        // If user is not allowed to see tasks, redirect to home page
        if (!TaskPermission::allowed()) {
            header('Location: /');
            exit;
        }

        Render::render('tasks/log');
        Render::render('tasks/list');
    }
}
