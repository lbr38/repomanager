<?php
/**
 *  Manually apply release SQL queries update
 */

define('ROOT', dirname(__FILE__, 2));

require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::loadFromLogin();

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
    echo PHP_EOL . 'Enabling maintenance page.' . PHP_EOL;
    $myupdate->setMaintenance('on');

    /**
     *  Only execute specified version update file
     */
    if (!empty($targetVersion)) {
        echo PHP_EOL . 'Executing SQL queries if there are...' . PHP_EOL;
        $myupdate->updateDB($targetVersion);

    /**
     *  Else execute all update files
     */
    } else {
        $myupdate->updateDB();
    }
} catch (Exception $e) {
    echo 'There was an error while executing update: ' . $e->getMessage();
    $error++;
}

echo PHP_EOL . 'Disabling maintenance page.' . PHP_EOL;

$myupdate->setMaintenance('off');

if ($error > 0) {
    exit(1);
}

exit(0);
