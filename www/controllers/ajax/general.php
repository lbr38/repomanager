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
 *  Acquit update log window and continue
 */
if ($action == "continueUpdate") {
    $myupdate = new \Controllers\Update();

    try {
        $myupdate->acquit();
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

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

response(HTTP_BAD_REQUEST, 'Invalid action');
