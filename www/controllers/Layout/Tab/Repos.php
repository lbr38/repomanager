<?php

namespace Controllers\Layout\Tab;

use Controllers\Layout\Container\Render;

class Repos
{
    public static function render()
    {
        Render::render('repos/kpi');
        Render::render('repos/list');
    }
}
