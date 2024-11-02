<?php

namespace Controllers\Layout\Tab;

class Repos
{
    public static function render()
    {
        \Controllers\Layout\Container\Render::render('repos/list');
        \Controllers\Layout\Container\Render::render('repos/properties');
    }
}
