<?php

namespace Controllers\Layout\Tab;

class Run
{
    public static function render()
    {
        \Controllers\Layout\Container\Render::render('tasks/log');
        \Controllers\Layout\Container\Render::render('tasks/list');
    }
}
