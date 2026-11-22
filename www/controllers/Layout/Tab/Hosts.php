<?php

namespace Controllers\Layout\Tab;

use Controllers\User\Permission\Host as HostPermission;
use Controllers\Layout\Container\Render;

class Hosts
{
    public static function render()
    {
        // If hosts management is disabled or user is not allowed to see hosts, redirect to home page
        if (MANAGE_HOSTS != 'true' or !HostPermission::allowed()) {
            header('Location: /');
            exit;
        }

        // Print hosts overview and list
        Render::render('hosts/overview');
        Render::render('hosts/list');
    }
}
