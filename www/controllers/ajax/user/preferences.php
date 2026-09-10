<?php

/**
 *  Edit user preferences
 */
if ($_POST['action'] == 'save' and !empty($_POST['preferences'])) {
    try {
        $userPreferenceController = new \Controllers\User\Preference\Preference();
        $userPreferenceController->set($_SESSION['id'], $_POST['preferences']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Preferences have been saved');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
