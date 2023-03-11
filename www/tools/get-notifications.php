<?php

define('ROOT', dirname(__FILE__, 2));
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::api();

$mynotification = new \Controllers\Notification();

try {
    $notifications = $mynotification->retrieve();
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}

exit(0);
