<?php

/**
 *  Update Repomanager
 */
if ($action == "updateRepomanager") {
    $myupdate = new \Controllers\Update();
    $myupdate->update();

    /**
     *  Always send HTTP_OK response, error warning on update is handled by a dedicated window
     */
    response(HTTP_OK, '');
}

/**
 *  Acquit log message
 */
if ($action == "acquitLog" && !empty($_POST['id'])) {
    $mylog = new \Controllers\Log\Log();

    try {
        $mylog->acquit($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, '');
}

/**
 *  Return specified container content
 */
if ($action == "getContainer" && !empty($_POST['container'])) {
    try {
        ob_start();
        \Controllers\Layout\Container\Render::render($_POST['container']);
        $content = ob_get_clean();
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get all layout containers state
 */
if ($action == "getContainerState") {
    $mycontainerState = new \Controllers\Layout\ContainerState();

    try {
        $result = $mycontainerState->get();
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, json_encode($result));
}

response(HTTP_BAD_REQUEST, 'Invalid action');
