<?php

namespace Controllers\Layout\Panel;

class Notification
{
    public static function render()
    {
        include_once(ROOT . '/views/includes/panels/notification.inc.php');
    }
}
