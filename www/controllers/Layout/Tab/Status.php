<?php

namespace Controllers\Layout\Tab;

use Controllers\Layout\Container\Render;

class Status
{
    public static function render()
    {
        // Only admin have access to this page
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        Render::render('status/health');
        Render::render('status/service');
        Render::render('status/monitoring');
    }
}
