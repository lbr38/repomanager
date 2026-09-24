<?php
/**
 *  Manually apply release SQL queries update
 */

define('ROOT', '/var/www/repomanager');
ini_set('memory_limit', '512M');

require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader();
new \Controllers\App\Main('minimal');
use \Controllers\Log\Cli as CliLog;

$updateController = new \Controllers\Update();
$error = 0;

/**
 *  Check if a release version is specified (with --release=''). If so then only this version dedicated migration file will be executed.
 *  Otherwise all files will be executed
 */
$getOptions = getopt(null, ["release:"]);

// Retrieve the target release version if specified
if (!empty($getOptions['release'])) {
    $targetVersion = $getOptions['release'];
}

try {
    CliLog::log('Enabling maintenance page');

    $updateController->setMaintenance('on');

    CliLog::log('Executing migration scripts...');

    // Only execute specified version migration
    if (!empty($targetVersion)) {
        CliLog::log('Executing ' . $targetVersion . ' migration...');
        $updateController->migrate($targetVersion);

    // Else execute all migrations
    } else {
        $updateController->migrate();
    }
} catch (Exception $e) {
    CliLog::error('There was an error while executing migration scripts', $e->getMessage());
    $error++;
} finally {
    CliLog::log('Disabling maintenance page');
    $updateController->setMaintenance('off');
}

if ($error > 0) {
    exit(1);
}

exit(0);
