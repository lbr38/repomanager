<?php
/**
 *  3.4.4 update
 */

if (!file_exists(REPOMANAGER_CONF)) {
    return;
}

if (!is_readable(REPOMANAGER_CONF)) {
    echo 'Error: main configuration file "' . REPOMANAGER_CONF . '" is not readable';
    return;
}

/**
 *  Get configuration
 */
$configuration = file_get_contents(REPOMANAGER_CONF);

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
if (!file_put_contents(REPOMANAGER_CONF, $configuration)) {
    echo 'Error while applying 3.4.4 update to main configuration file';
}
