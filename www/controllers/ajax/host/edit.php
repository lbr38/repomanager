<?php
/**
 *  Edit host description or tags (fields of the host page)
 */
if (in_array($action, ['description', 'tags']) and !empty($_POST['hostId']) and is_numeric($_POST['hostId'])) {
    $params = [];
    $messages = [
        'description' => 'Description has been saved',
        'tags'        => 'Tags have been saved'
    ];

    if ($action == 'description' and isset($_POST['description'])) {
        $params['description'] = $_POST['description'];
    }

    // Tags are sent as a comma-separated string
    if ($action == 'tags' and isset($_POST['tags']) and is_string($_POST['tags'])) {
        $params['tags'] = explode(',', $_POST['tags']);
    }

    try {
        if (empty($params)) {
            throw new Exception('Invalid action');
        }

        $hostController = new \Controllers\Host\Host();
        $hostController->edit((int) $_POST['hostId'], $params);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $messages[$action]);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
