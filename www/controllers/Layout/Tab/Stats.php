<?php

namespace Controllers\Layout\Tab;

class Stats
{
    public static function render()
    {
        \Controllers\Layout\Container\Render::render('stats/list');
    }
}
