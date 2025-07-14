<?php
/**
 *  If the user is not an administrator or does not have permission to edit repositories, prevent access to this panel.
 */
if (!IS_ADMIN and !in_array('edit', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) {
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
