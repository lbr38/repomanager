<?php
/**
 *  Edit hosts settings
 */
if ($action == 'edit' and isset($_POST['complianceThresholdCount']) and isset($_POST['complianceThresholdDays']) and isset($_POST['complianceSecurityUpdate']) and isset($_POST['complianceRebootRequired'])) {
    $hostController = new \Controllers\Host\Host();

    try {
        $hostController->setSettings($_POST['complianceThresholdCount'], $_POST['complianceThresholdDays'], $_POST['complianceSecurityUpdate'], $_POST['complianceRebootRequired']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Settings updated');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
