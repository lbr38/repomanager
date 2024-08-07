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

        /**
         *  If user is admin, print host group and host settings panels
         */
        if (IS_ADMIN) {
            \Controllers\Layout\Panel\Hosts\Group::render();
            \Controllers\Layout\Panel\Hosts\Settings::render();
        }
    }
}
