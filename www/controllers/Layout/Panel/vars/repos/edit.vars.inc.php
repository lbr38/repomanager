<?php
use \Controllers\User\Permission\Repo as RepoPermission;

/**
 *  If the user is not an administrator or does not have permission to edit repositories, prevent access to this panel.
 */
if (!RepoPermission::allowedAction('edit')) {
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
