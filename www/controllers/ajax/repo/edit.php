<?php
/**
 *  Validate form and edit repositories
 */
if ($_POST['action'] == 'validateForm' and !empty($_POST['params'])) {
    $repoEditForm = new \Controllers\Repo\Edit\Form();

    try {
        $params = json_decode($_POST['params'], true, 512, JSON_THROW_ON_ERROR);
        $repoEditForm->validate($params);
        $repoEditForm->edit($params);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Successfully edited.');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
