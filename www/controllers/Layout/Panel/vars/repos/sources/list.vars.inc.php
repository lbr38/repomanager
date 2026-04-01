<?php
use Controllers\User\Permission\Repo as RepoPermission;

// If the user does not have permission to edit source repositories, prevent access to this panel.
if (!RepoPermission::allowedAction('edit-source')) {
    throw new Exception('You are not allowed to access this panel');
}
