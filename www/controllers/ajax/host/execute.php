<?php
$myhostExecuteController = new \Controllers\Host\Execute();

/**
 *  Install host packages
 */
if ($action == 'install-packages' and !empty($_POST['params'])) {
    try {
        $myhostExecuteController->installPackages($_POST['params']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Request to install packages sent');
}

/**
 *  Update host packages
 */
if ($action == 'update-packages' and !empty($_POST['params'])) {
    try {
        $myhostExecuteController->updatePackages($_POST['params']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Request to update packages sent');
}

/**
 *  Update selected available packages
 */
if ($action == 'update-selected-available-packages' and !empty($_POST['hostId']) and !empty($_POST['packages'])) {
    try {
        $myhostExecuteController->updateSelectedAvailablePackages($_POST['hostId'], $_POST['packages']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Request to update selected packages sent');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
