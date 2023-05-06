<?php

namespace Controllers\Layout\Tab;

class Host
{
    public static function render()
    {
        $myhost = new \Controllers\Host();

        include_once(ROOT . '/views/host.template.php');
    }
}
