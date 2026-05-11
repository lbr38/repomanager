<?php

namespace Controllers\Layout\Tab;

class Run
{
    public static function render()
    {
        /**
         *  /run now redirects to "/tasks", which is the main page for task management
         *  If it is followed by an ID (ex: /run/123), then redirects to /task/123
         */
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // If the path is exactly "/run", redirect to "/tasks"
        if ($currentPath === '/run') {
            header('Location: /tasks');
            exit;
        }

        // If the path starts with "/run/" followed by an ID, redirect to "/task/{ID}"
        if (preg_match('#^/run/(\d+)$#', $currentPath, $matches)) {
            $taskId = $matches[1];
            header('Location: /task/' . $taskId);
            exit;
        }
    }
}
