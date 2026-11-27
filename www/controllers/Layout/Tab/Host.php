<?php

namespace Controllers\Layout\Tab;

use Controllers\User\Permission\Host as HostPermission;

class Host
{
    public static function render()
    {
        // If hosts management is disabled or user is not allowed to see hosts, redirect to home page
        if (MANAGE_HOSTS != 'true' or !HostPermission::allowed()) {
            header('Location: /');
            exit;
        }

        $myhost = new \Controllers\Host\Host();

        include_once(ROOT . '/views/host.template.php');
    }
}
