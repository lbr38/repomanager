<?php

namespace Controllers\Layout\Panel;

class HostGroup
{
    public static function render()
    {
        $myhost = new \Controllers\Host();
        $mygroup = new \Controllers\Group('host');

        /**
         *  Get hosts groups list
         */
        $hostGroupsList = $mygroup->listAll();

        include_once(ROOT . '/views/includes/panels/hosts/groups.inc.php');

        unset($myhost, $mygroup);
    }
}
