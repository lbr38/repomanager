<?php

namespace Controllers\Layout\Tab;

class Status
{
    public static function render()
    {
        // Only admin have access to this page
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        \Controllers\Layout\Container\Render::render('status/health');
        \Controllers\Layout\Container\Render::render('status/service');
        \Controllers\Layout\Container\Render::render('status/monitoring');
    }
}
