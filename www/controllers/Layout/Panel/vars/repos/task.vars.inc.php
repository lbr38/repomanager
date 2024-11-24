<?php
if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel');
}

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
$formContent = $myTaskForm->get($item['action'], json_decode($item['repos'], true));
