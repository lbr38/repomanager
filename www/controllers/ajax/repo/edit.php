<?php
/**
 *  Edit repository or mirror description, tags or snapshots retention (fields of the repositories list)
 *  Mirrors are listed with repositories and use the same edit fields, their Id is prefixed with 'mirror-'
 */
if (in_array($_POST['action'], ['description', 'tags', 'retention']) and !empty($_POST['repoId'])) {
    $params = [];
    $messages = [
        'description' => 'Description has been saved',
        'tags'        => 'Tags have been saved',
        'retention'   => 'Retention has been saved'
    ];

    if ($_POST['action'] == 'description' and isset($_POST['description'])) {
        $params['description'] = $_POST['description'];
    }

    // Tags are sent as a comma-separated string
    if ($_POST['action'] == 'tags' and isset($_POST['tags'])) {
        $params['tags'] = explode(',', $_POST['tags']);
    }

    if ($_POST['action'] == 'retention') {
        $params['retention'] = $_POST['retention'] ?? null;
        $params['retention-keep-all'] = $_POST['keepAll'] ?? 'false';
    }

    try {
        if (empty($params)) {
            throw new Exception('Invalid action');
        }

        if (str_starts_with($_POST['repoId'], 'mirror-')) {
            $editController = new \Controllers\Mirror\Edit();
            $params['mirror-id'] = substr($_POST['repoId'], strlen('mirror-'));
        } else {
            $editController = new \Controllers\Repo\Edit\Form();
            $params['repo-id'] = $_POST['repoId'];
        }

        $editController->validate($params);
        $editController->edit($params);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $messages[$_POST['action']]);
}

/**
 *  Edit mirror properties from the mirror edit panel: snapshots retention and, for an openbsd mirror, its versions, content and architectures
 *  Parameters are sent as JSON, so that an emptied list is sent (and rejected) instead of being omitted
 */
if ($_POST['action'] == 'mirror-properties' and !empty($_POST['mirrorId']) and !empty($_POST['params'])) {
    $editController = new \Controllers\Mirror\Edit();

    try {
        try {
            $values = json_decode($_POST['params'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode form parameters: ' . $e->getMessage());
        }

        if (!is_array($values)) {
            throw new Exception('Invalid form parameters');
        }

        $params = ['mirror-id' => $_POST['mirrorId']];

        foreach (['retention', 'retention-keep-all', 'versions', 'content', 'arch'] as $key) {
            if (array_key_exists($key, $values)) {
                $params[$key] = $values[$key];
            }
        }

        $editController->validate($params);
        $editController->edit($params);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Mirror properties have been saved');
}

/**
 *  Validate form and edit repository properties
 */
if ($_POST['action'] == 'validate-execute' and !empty($_POST['params'])) {
    $repoEditForm = new \Controllers\Repo\Edit\Form();

    try {
        try {
            $params = json_decode($_POST['params'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode form parameters: ' . $e->getMessage());
        }

        $repoEditForm->validate($params);
        $repoEditForm->edit($params);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Successfully edited.');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
