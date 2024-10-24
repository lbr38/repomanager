<?php
if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel');
}

$myhost = new \Controllers\Host();

/**
 *  Getting general hosts threshold settings
 */
$hostsSettings = $myhost->getSettings();

/**
 *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
 */
$pkgs_count_considered_outdated = $hostsSettings['pkgs_count_considered_outdated'];

/**
 *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (critical)
 */
$pkgs_count_considered_critical = $hostsSettings['pkgs_count_considered_critical'];

unset($myhost, $hostsSettings);
