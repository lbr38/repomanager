<?php

define('ROOT', '/var/www/repomanager');

/**
 *  Header and allowed methods
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once(ROOT . '/controllers/Autoloader.php');
require_once(ROOT . '/controllers/Api/Api.php');

new \Controllers\Autoloader();

/**
 *  Call and execute main API controller
 */
$myapi = new \Controllers\Api\Api();
$myapi->run();

exit;
