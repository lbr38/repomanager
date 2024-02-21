<?php

namespace Controllers\Layout\Tab;

class Cves
{
    public static function render()
    {
        $mycve = new \Controllers\Cve\Cve();

        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        /**
         *  Print CVEs list
         */
        \Controllers\Layout\Container\Render::render('cves/list');
    }
}
