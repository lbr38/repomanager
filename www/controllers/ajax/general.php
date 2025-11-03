<?php

/**
 *  Acquit log message
 */
if ($action == "acquitLog" && !empty($_POST['id'])) {
    $mylog = new \Controllers\Log\Log();

    try {
        $mylog->acquit($_POST['id']);
    } catch (Exception $e) {
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
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Return specified table content
 */
if ($action == "getTable" && !empty($_POST['table']) && isset($_POST['offset'])) {
    try {
        ob_start();
        \Controllers\Layout\Table\Render::render($_POST['table'], $_POST['offset']);
        $content = ob_get_clean();
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Return specified panel content
 */
if ($action == "get-panel" && !empty($_POST['name']) && isset($_POST['params'])) {
    try {
        ob_start();
        \Controllers\Layout\Panel\Render::render($_POST['name'], $_POST['params']);
        $content = ob_get_clean();
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
