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

$myupdate = new \Controllers\Update();
$error = 0;

/**
 *  Check if a release version is specified (with --release=''). If so then only this version dedicated update file will be executed.
 *  Else all files will be executed
 */
$getOptions = getopt(null, ["release:"]);

/**
 *  Retrieve the update ID to process
 */
if (!empty($getOptions['release'])) {
    $targetVersion = $getOptions['release'];
}

try {
    CliLog::log('Enabling maintenance page');

    $myupdate->setMaintenance('on');

    CliLog::log('Updating database');

    /**
     *  Only execute specified version update file
     */
    if (!empty($targetVersion)) {
        CliLog::log('Executing ' . $targetVersion . ' release SQL queries if there are...');
        $myupdate->updateDB($targetVersion);

    /**
     *  Else execute all update files
     */
    } else {
        $myupdate->updateDB();
    }
} catch (Exception $e) {
    CliLog::error('There was an error while executing update', $e->getMessage());
    $error++;
} finally {
    CliLog::log('Disabling maintenance page');
    $myupdate->setMaintenance('off');
}

if ($error > 0) {
    exit(1);
}

exit(0);
