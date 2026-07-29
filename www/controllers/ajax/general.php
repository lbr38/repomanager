<?php
use Controllers\Layout\Container\Render as ContainerRender;
use Controllers\Layout\Table\Render as TableRender;
use Controllers\Layout\Panel\Render as PanelRender;
use Controllers\Exception\InvalidContainerException;
use Controllers\Exception\InvalidPanelException;
use Controllers\Exception\InvalidTableException;
use Controllers\Log\Log;

/**
 *  Acquit log message
 */
if ($action == 'acquit-log' && !empty($_POST['id'])) {
    $logController = new Log();

    try {
        $logController->acquit($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, '');
}

/**
 *  Return specified container content
 */
if ($action == 'get-container' && !empty($_POST['container'])) {
    try {
        ob_start();
        ContainerRender::render($_POST['container']);
        $content = ob_get_clean();
    } catch (InvalidContainerException $e) {
        ob_end_clean();
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Return specified table content
 */
if ($action == 'get-table' && !empty($_POST['table']) && isset($_POST['offset'])) {
    try {
        ob_start();
        TableRender::render($_POST['table'], $_POST['offset']);
        $content = ob_get_clean();
    } catch (InvalidTableException $e) {
        ob_end_clean();
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Return specified panel content
 */
if ($action == 'get-panel' && !empty($_POST['name']) && isset($_POST['params'])) {
    try {
        ob_start();
        PanelRender::render($_POST['name'], $_POST['params']);
        $content = ob_get_clean();
    } catch (InvalidPanelException $e) {
        ob_end_clean();
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
