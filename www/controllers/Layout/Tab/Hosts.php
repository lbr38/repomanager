<?php

namespace Controllers\Layout\Tab;

class Hosts
{
    public static function render()
    {
        /**
         *  Print hosts overview and list
         */
        \Controllers\Layout\Container\Render::render('hosts/overview');
        \Controllers\Layout\Container\Render::render('hosts/list');
    }
}
