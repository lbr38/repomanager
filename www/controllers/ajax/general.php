<?php

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

/**
 *  Return specified alert confirm box content
 */
if ($action == "getConfirmBox" && !empty($_POST['name'])) {
    try {
        /**
         *  Check if confirm box exists
         */
        if (!file_exists(ROOT . '/templates/alert/' . $_POST['name'] . '.php')) {
            throw new \Exception('Invalid confirm box');
        }

        ob_start();
        include_once(ROOT . '/templates/alert/' . $_POST['name'] . '.php');
        $content = ob_get_clean();
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
