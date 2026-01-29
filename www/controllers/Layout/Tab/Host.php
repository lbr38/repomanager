<?php

namespace Controllers\Layout\Tab;

use \Controllers\User\Permission\Host as HostPermission;

class Host
{
    public static function render()
    {
        // If user is not allowed to see hosts, redirect to home page
        if (!HostPermission::allowed()) {
            header('Location: /');
            exit;
        }

        $myhost = new \Controllers\Host();

        include_once(ROOT . '/views/host.template.php');
    }
}
