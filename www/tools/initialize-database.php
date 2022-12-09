<?php
/**
 *  Manually check and initialize main database
 */

define('ROOT', dirname(__FILE__, 2));

require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::loadFromLogin();

$myconn = new \Models\Connection('main');

if (!$myconn->checkMainTables()) {
    /**
     *  Si la vérification a échouée alors on quitte.
     */
    echo 'There was an error while initializing main database' . PHP_EOL;
    exit(1);
}

echo 'Main database check and initialization successfull' . PHP_EOL;
exit(0);
