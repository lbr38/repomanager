<?php

namespace Controllers\Layout\Tab;

class History
{
    public static function render()
    {
        $filterByUser = false;

        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        \Controllers\Layout\Container\Render::render('history/list');
    }
}
