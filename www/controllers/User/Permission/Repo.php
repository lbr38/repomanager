<?php

namespace Controllers\User\Permission;

class Repo
{
    public static function allowedAction(string $action) : bool
    {
        // Admins are allowed to do everything
        if (IS_ADMIN) {
            return true;
        }

        if (isset(USER_PERMISSIONS['repositories']['allowed-actions']) && in_array($action, USER_PERMISSIONS['repositories']['allowed-actions'])) {
            return true;
        }

        return false;
    }
}
