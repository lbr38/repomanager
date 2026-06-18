<?php
/**
 *  Return specified KPI card data
 */
if ($action == 'get' && !empty($_POST['id'])) {
    try {
        $data = \Controllers\Layout\Kpi\Kpi::get($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $data);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
