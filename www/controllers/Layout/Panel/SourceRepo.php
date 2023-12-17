<?php

namespace Controllers\Layout\Panel;

class SourceRepo
{
    public static function render()
    {
        include_once(ROOT . '/views/includes/panels/repos/sources.inc.php');
    }
}
