<?php

/*
 *  Acquit update log window
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

response(HTTP_BAD_REQUEST, 'Invalid action');
