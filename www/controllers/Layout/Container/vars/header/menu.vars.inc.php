<?php
/**
 *  Get current CPU load
 */
$currentLoad = sys_getloadavg();
$currentLoad = substr($currentLoad[0], 0, 4);
$currentLoadColor = 'green';

if ($currentLoad >= 2) {
    $currentLoadColor = 'yellow';
}
if ($currentLoad >= 3) {
    $currentLoadColor = 'red';
}
