<?php

namespace Controllers\Layout\Tab;

class Browse
{
    public static function render()
    {
        \Controllers\Layout\Container\Render::render('browse/list');
        \Controllers\Layout\Container\Render::render('browse/actions');
    }
}
