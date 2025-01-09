<?php
if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel');
}

$myEditForm = new \Controllers\Repo\Edit\Form();

/**
 *  Check that action and repos params have been sent
 */
if (empty($item['repos'])) {
    throw new Exception('Task repositories required');
}

$slidePanelTitle = 'EDIT REPOSITORY & SNAPSHOT PROPERTIES';

/**
 *  Get form content
 */
$formContent = $myEditForm->get(json_decode($item['repos'], true));
