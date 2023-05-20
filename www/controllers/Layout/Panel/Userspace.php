<?php

namespace Controllers\Layout\Panel;

class Userspace
{
    public static function render()
    {
        include_once(ROOT . '/views/includes/panels/userspace.inc.php');
    }
}
