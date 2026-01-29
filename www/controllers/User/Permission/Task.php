<?php

namespace Controllers\User\Permission;

class Task
{
    /**
     *  Check if the user has any permission on tasks
     */
    public static function allowed(): bool
    {
        // Admins are allowed to do everything
        if (IS_ADMIN) {
            return true;
        }

        if (isset(USER_PERMISSIONS['tasks']['allowed-actions']) && !empty(USER_PERMISSIONS['tasks']['allowed-actions'])) {
            return true;
        }

        return false;
    }

    /**
     *  Check if the user is allowed to perform a specific action on tasks
     */
    public static function allowedAction(string $action): bool
    {
        // Admins are allowed to do everything
        if (IS_ADMIN) {
            return true;
        }

        if (isset(USER_PERMISSIONS['tasks']['allowed-actions']) && in_array($action, USER_PERMISSIONS['tasks']['allowed-actions'])) {
            return true;
        }

        return false;
    }
}
