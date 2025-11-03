<?php

/**
 *  Get CPU usage
 */
if ($_POST['action'] == 'get-cpu-usage') {
    try {
        $content = \Controllers\System\Monitoring\Cpu::getUsage();
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
