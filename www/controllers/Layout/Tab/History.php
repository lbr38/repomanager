<?php

namespace Controllers\Layout\Tab;

use Controllers\Layout\Container\Render;

class History
{
    public static function render()
    {
        // Only admin have access to this page
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        Render::render('history/list');
    }
}
