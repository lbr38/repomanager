<?php

namespace Controllers\Layout\Tab;

class Run
{
    public static function render()
    {
        \Controllers\Layout\Container\Render::render('operations/log');
        \Controllers\Layout\Container\Render::render('operations/list');
    }
}
