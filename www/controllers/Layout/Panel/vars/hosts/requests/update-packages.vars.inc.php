<?php
if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel');
}

$myhostController = new \Controllers\Host();

$hosts = [];

/**
 *  Build the list of hosts with their id and hostname
 */

/**
 *  Case of a single host
 */
if (!empty($item['hostId'])) {
    if ($myhostController->existsId($item['hostId'])) {
        $hostname = $myhostController->getHostnameById($item['hostId']);
        $hosts[] = array('id' => $item['hostId'], 'hostname' => $hostname);
    }
}

/**
 *  Case of multiple hosts
 */
if (!empty($item['hostsId'])) {
    foreach ($item['hostsId'] as $hostId) {
        if ($myhostController->existsId($hostId)) {
            $hostname = $myhostController->getHostnameById($hostId);
            $hosts[] = array('id' => $hostId, 'hostname' => $hostname);
        }
    }
}
