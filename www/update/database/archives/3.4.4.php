<?php
/**
 *  3.4.4 update
 */

$conf = '/var/lib/repomanager/configurations/repomanager.conf';

if (!file_exists($conf)) {
    return;
}

if (!is_readable($conf)) {
    echo 'Error: main configuration file "' . $conf . '" is not readable';
    return;
}

/**
 *  Get configuration
 */
$configuration = file_get_contents($conf);

/**
 *  Replace values to 'true' or 'false'
 */
$configuration = str_replace('"enabled"', '"true"', $configuration);
$configuration = str_replace('"disabled"', '"false"', $configuration);
$configuration = str_replace('"yes"', '"true"', $configuration);
$configuration = str_replace('"no"', '"false"', $configuration);

/**
 *  Write configuration
 */
if (!file_put_contents($conf, $configuration)) {
    echo 'Error while applying 3.4.4 update to main configuration file';
}
