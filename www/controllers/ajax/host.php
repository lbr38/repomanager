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
if ($action == "getHostsWithPackage" and !empty($_POST['hosts']) and !empty($_POST['package']) and isset($_POST['version']) and isset($_POST['strictName']) and isset($_POST['strictVersion'])) {
    $myhost = new \Controllers\Host();

    try {
        $result = $myhost->getHostsWithPackage($_POST['hosts'], $_POST['package'], $_POST['version'], $_POST['strictName'], $_POST['strictVersion']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, json_encode($result));
}

/**
 *  Show request log details
 */
if ($action == "getRequestLog" and !empty($_POST['id'])) {
    $hostRequestController = new \Controllers\Host\Request();

    try {
        $content = $hostRequestController->getRequestLog($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Show request package log details
 */
if ($action == "getRequestPackageLog" and !empty($_POST['id']) and !empty($_POST['package']) and !empty($_POST['status'])) {
    $hostRequestController = new \Controllers\Host\Request();

    try {
        $content = $hostRequestController->getRequestPackageLog($_POST['id'], $_POST['package'], $_POST['status']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Cancel a request sent to a host
 */
if ($action == "cancelRequest" and !empty($_POST['id'])) {
    $hostRequestController = new \Controllers\Host\Request();

    try {
        $hostRequestController->cancel($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Request has been canceled');
}

/**
 *  Get package history timeline
 */
if ($action == "getPackageTimeline" and !empty($_POST['hostid']) and !empty($_POST['packagename'])) {
    $hostPackageController = new \Controllers\Host\Package\Package($_POST['hostid']);

    try {
        $content = $hostPackageController->getTimeline($_POST['packagename']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get event details (installed packages, updated packages...)
 */
if ($action == "getEventDetails" and !empty($_POST['hostId']) and !empty($_POST['eventId']) and !empty($_POST['packageState'])) {
    $hostPackageEventController = new \Controllers\Host\Package\Event($_POST['hostId']);

    try {
        $content = $hostPackageEventController->getDetails(\Controllers\Utils\Validate::string($_POST['eventId']), \Controllers\Utils\Validate::string($_POST['packageState']));
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
