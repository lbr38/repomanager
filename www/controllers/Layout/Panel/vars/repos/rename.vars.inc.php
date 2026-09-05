<?php
use \Controllers\User\Permission\Repo as RepoPermission;

$renameFormController = new \Controllers\Repo\Edit\Form();
$taskFormController = new \Controllers\Task\Form\Form();

//  If the user is not an administrator or does not have permission to edit repositories, prevent access to this panel.
if (!RepoPermission::allowedAction('edit')) {
    throw new Exception('You are not allowed to access this panel');
}

// Check that action and repos params have been sent
if (empty($item['repos'])) {
    throw new Exception('Repositories required');
}

$slidePanelTitle = 'RENAME REPOSITORY';

// Get form content
try {
    $formContent = $taskFormController->get('rename', json_decode($item['repos'], true));
} catch (JsonException $e) {
    throw new Exception('Error while retrieving the form content: ' . $e->getMessage());
}
