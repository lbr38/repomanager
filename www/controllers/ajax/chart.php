<?php
/**
 *  Return specified chart data
 */
if ($action == 'get' && !empty($_POST['id']) and !empty($_POST['days'])) {
    try {
        $data = \Controllers\Layout\Chart\Chart::get($_POST['id'], $_POST['days']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $data);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
