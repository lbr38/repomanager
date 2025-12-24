<?php
use \Controllers\User\Permission\Repo as RepoPermission;

$myTaskForm = new \Controllers\Task\Form\Form();

/**
 *  Check that action and repos params have been sent
 */
if (empty($item['action'])) {
    throw new Exception('Task action is required');
}
if (empty($item['repos'])) {
    throw new Exception('Task repositories required');
}

/**
 *  Check that action is valid
 */
if (!in_array($item['action'], ['update', 'env', 'duplicate', 'delete', 'rebuild'])) {
    throw new Exception('Invalid action: ' . $item['action']);
}

/**
 *  If the user is not an administrator or does not have permission to perform the specified action, prevent access to this panel.
 */
if (!IS_ADMIN and !RepoPermission::allowedAction($item['action'])) {
    throw new Exception('You are not allowed to access this panel');
}

if ($item['action'] == 'update') {
    $slidePanelTitle = 'UPDATE';
}
if ($item['action'] == 'env') {
    $slidePanelTitle = 'POINT AN ENVIRONMENT';
}
if ($item['action'] == 'duplicate') {
    $slidePanelTitle = 'DUPLICATE';
}
if ($item['action'] == 'delete') {
    $slidePanelTitle = 'DELETE REPOSITORY SNAPSHOT';
}
if ($item['action'] == 'rebuild') {
    $slidePanelTitle = 'REBUILD REPOSITORY';
}

/**
 *  Get form content for the specified action
 */
try {
    $formContent = $myTaskForm->get($item['action'], json_decode($item['repos'], true));
} catch (JsonException $e) {
    throw new Exception('Error decoding repositories: ' . $e->getMessage());
}
