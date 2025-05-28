<?php
/**
 *  Manually apply release SQL queries update
 */

define('ROOT', '/var/www/repomanager');
ini_set('memory_limit', '512M');

require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('minimal');

$myupdate = new \Controllers\Update();
$error = 0;

/**
 *  Check if a release version is specified (with --release=''). If so then only this version dedicated update file will be executed.
 *  Else all files will be executed
 */
$getOptions = getopt(null, ["release:"]);

/**
 *  Récupération de l'Id de l'opération à traiter
 */
if (!empty($getOptions['release'])) {
    $targetVersion = $getOptions['release'];
}

try {
    echo '[' . date('D M j H:i:s') . '] Enabling maintenance page' . PHP_EOL;
    $myupdate->setMaintenance('on');

    echo '[' . date('D M j H:i:s') . '] Updating database' . PHP_EOL;

    /**
     *  Only execute specified version update file
     */
    if (!empty($targetVersion)) {
        echo '[' . date('D M j H:i:s') . '] Executing ' . $targetVersion . ' release SQL queries if there are...' . PHP_EOL;
        $myupdate->updateDB($targetVersion);

    /**
     *  Else execute all update files
     */
    } else {
        $myupdate->updateDB();
    }
} catch (Exception $e) {
    echo '[' . date('D M j H:i:s') . '] There was an error while executing update: ' . $e->getMessage() . PHP_EOL;
    $error++;
}

echo '[' . date('D M j H:i:s') . '] Disabling maintenance page' . PHP_EOL;

$myupdate->setMaintenance('off');

if ($error > 0) {
    exit(1);
}

exit(0);
