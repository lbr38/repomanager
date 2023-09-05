<?php

define("ROOT", dirname(__FILE__, 2));
require_once(ROOT . "/controllers/Autoloader.php");
new \Controllers\Autoloader('api');

ini_set('memory_limit', '256M');

$mylog = new \Controllers\Log\Log();
$validActions = ['create', 'new', 'update', 'duplicate', 'delete', 'env', 'reconstruct'];

/**
 *  Getting options from command line: operation Id is required and cannot be empty.
 *
 *  First parameter passed to getopt is null: we don't want to work with short options.
 *  More infos about getopt() : https://blog.pascal-martin.fr/post/php-5.3-getopt-parametres-ligne-de-commande/
 */
$getOptions = getopt(null, ["id:"]);

/**
 *  Récupération de l'Id de l'opération à traiter
 */
if (empty($getOptions['id'])) {
    $mylog->log('error', 'Operation run', 'Operation Id is not defined.');
    echo 'Error: operation Id is not defined.' . PHP_EOL;
    exit(1);
}

$poolId = $getOptions['id'];

if (!file_exists(POOL . '/' . $poolId . '.json')) {
    $mylog->log('error', 'Operation run', 'Cannot get operation details (Id ' . $poolId . ') from pool file: file not found.');
    echo "Error: cannot get operation details (Id $poolId) from pool file: file not found." . PHP_EOL;
    exit(1);
}

/**
 *  Getting operation details
 */
$operation_params = json_decode(file_get_contents(POOL . '/' . $poolId . '.json'), true);

/**
 *  Default values
 */
$targetGroup = 'nogroup';
$targetDescription = 'nodescription';

/**
 *  Getting action
 */
if (empty($operation_params['action'])) {
    $mylog->log('error', 'Operation run', 'Action not specified.');
    echo 'Action not specified.' . PHP_EOL;
    exit(1);
}

$action = $operation_params['action'];

// TODO : remplacer new par create
if ($action == 'new') {
    $action = 'create';
}

/**
 *  Check that action is valid
 */
if (!in_array($action, $validActions)) {
    $mylog->log('error', 'Operation run', 'Invalid action: ' . $action);
    echo 'Unknown operation: invalid action.' . PHP_EOL;
    exit(1);
}

/**
 *  Generate controller name
 */
$controllerPath = '\Controllers\Repo\Operation\\' . ucfirst($action);

try {
    new $controllerPath($poolId, $operation_params);
} catch (\Exception $e) {
    // $mylog->log('error', 'Operation run', $e->getMessage());
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
