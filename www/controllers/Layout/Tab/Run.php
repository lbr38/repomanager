<?php

namespace Controllers\Layout\Tab;

class Run
{
    public static function render()
    {
        /**
         *  Stop button to kill a running operation
         */
        if (!empty($_GET['stop'])) {
            $opToStop = new \Controllers\Operation();
            $opToStop->kill(\Controllers\Common::validateData($_GET['stop'])); // $_GET['stop'] contains the operation PID
        }

        \Controllers\Layout\Container\Render::render('operations/log');
        \Controllers\Layout\Container\Render::render('operations/list');
    }
}
