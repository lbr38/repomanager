<?php
cli_set_process_title('repomanager.operation-run');

define('ROOT', '/var/www/repomanager');
require_once(ROOT . "/controllers/Autoloader.php");
new \Controllers\Autoloader('api');

ini_set('memory_limit', '256M');

$myTaskPool = new \Controllers\Task\Pool\Pool();
$mylog = new \Controllers\Log\Log();

/**
 *  Getting options from command line: operation Id is required and cannot be empty.
 *
 *  First parameter passed to getopt is null: we don't want to work with short options.
 *  More infos about getopt() : https://blog.pascal-martin.fr/post/php-5.3-getopt-parametres-ligne-de-commande/
 */
$getOptions = getopt(null, ["id:"]);

try {
    /**
     *  Retrieve operation Id
     */
    // if (empty($getOptions['id'])) {
    //     throw new Exception('Operation Id is not defined');
    // }

    // $poolId = $getOptions['id'];

    // if (!file_exists(POOL . '/' . $poolId . '.json')) {
    //     throw new Exception('Cannot get operation details (Id ' . $poolId . ') from pool file: file not found.');
    // }

    // /**
    //  *  Getting operation details
    //  */
    // $operation_params = json_decode(file_get_contents(POOL . '/' . $poolId . '.json'), true);

    // /**
    //  *  Default values
    //  */
    // $targetGroup = 'nogroup';
    // $targetDescription = 'nodescription';

    // /**
    //  *  Getting action
    //  */
    // if (empty($operation_params['action'])) {
    //     throw new Exception('Action not specified');
    // }

    // $action = $operation_params['action'];

    // // TODO : remplacer new par create
    // if ($action == 'new') {
    //     $action = 'create';
    // }

    // /**
    //  *  Check that action is valid
    //  */
    // if (!in_array($action, $validActions)) {
    //     throw new Exception('Invalid action: ' . $action);
    // }

    /**
     *  Retrieve task Id
     */
    if (empty($getOptions['id'])) {
        throw new Exception('Task Id is not defined');
    }

    $poolId = $getOptions['id'];

    /**
     *  Retrieve task details
     */
    $taskParams = $myTaskPool->getById($poolId);

    if (empty($taskParams)) {
        throw new Exception('Cannot get task details from tasks pool Id #' . $poolId . ': empty results.');
    }

    $taskParams = json_decode($taskParams['Parameters'], true);

    if (empty($taskParams['action'])) {
        throw new Exception('Action not specified');
    }

    $action = $taskParams['action'];

    // TODO : replace new with create
    if ($action == 'new') {
        $action = 'create';
    }

    /**
     *  Generate controller name
     */
    $controllerPath = '\Controllers\Task\Repo\\' . ucfirst($action);

    /**
     *  Check if class exists, otherwise the action might be invalid
     */
    if (!class_exists($controllerPath)) {
        throw new Exception('Invalid action: ' . $action);
    }

    /**
     *  Instantiate controller and execute action
     */
    $controller = new $controllerPath($poolId, $taskParams);
    $controller->execute();
} catch (Exception $e) {
    $mylog->log('error', 'Task run', $e->getMessage());
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
