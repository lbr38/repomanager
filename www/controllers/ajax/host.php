<?php

/**
 *  Edit hosts settings
 */
if ($action == 'editSettings' and isset($_POST['packagesConsideredOutdated']) and isset($_POST['packagesConsideredCritical'])) {
    $myhost = new \Controllers\Host();

    try {
        $myhost->setSettings($_POST['packagesConsideredOutdated'], $_POST['packagesConsideredCritical']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Parameters have been taken into account');
}

/**
 *  Get all hosts that have the specified kernel
 */
if ($action == "getHostWithKernel" and !empty($_POST['kernel'])) {
    $myhost = new \Controllers\Host();

    try {
        $content = json_encode($myhost->getHostWithKernel($_POST['kernel']));
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get all hosts that have the specified profile
 */
if ($action == "getHostWithProfile" and !empty($_POST['profile'])) {
    $myhost = new \Controllers\Host();

    try {
        $content = json_encode($myhost->getHostWithProfile($_POST['profile']));
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Search if a package is installed on a host (from Search package form)
 */
if ($action == "getHostsWithPackage" and !empty($_POST['hostsIdArray']) and !empty($_POST['package'])) {
    $myhost = new \Controllers\Host();

    try {
        $result = $myhost->getHostsWithPackage($_POST['hostsIdArray'], $_POST['package']);
        /**
         *  If no hosts have been found
         */
        if ($result === false) {
            response(HTTP_BAD_REQUEST, 'Package has not been found on any hosts');
        }
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, json_encode($result));
}

/*
 *  Execute an action on selected host(s)
 */
if ($action == "hostExecAction" and !empty($_POST['exec']) and !empty($_POST['hosts_array'])) {
    $myhost = new \Controllers\Host();

    try {
        $content = $myhost->hostExec($_POST['hosts_array'], $_POST['exec']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get package history timeline
 */
if ($action == "getPackageTimeline" and !empty($_POST['hostid']) and !empty($_POST['packagename'])) {
    $myhost = new \Controllers\Host();

    try {
        $content = $myhost->getPackageTimeline($_POST['hostid'], $_POST['packagename']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get event details (installed packages, updated packages...)
 */
if ($action == "getEventDetails" and !empty($_POST['hostId']) and !empty($_POST['eventId']) and !empty($_POST['packageState'])) {
    $myhost = new \Controllers\Host();

    try {
        $content = $myhost->getEventDetails($_POST['hostId'], $_POST['eventId'], $_POST['packageState']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
