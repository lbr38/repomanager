<?php

namespace Controllers\User\Permission;

class Mirror
{
    /**
     *  Check if the user is allowed to perform a specific action on mirrors
     *  Mirrors viewing is granted with the repositories view permissions (see Repo::allowedToViewMirror())
     */
    public static function allowedAction(string $action) : bool
    {
        // Admins are allowed to do everything
        if (IS_ADMIN) {
            return true;
        }

        if (isset(USER_PERMISSIONS['mirrors']['allowed-actions']) && in_array($action, USER_PERMISSIONS['mirrors']['allowed-actions'])) {
            return true;
        }

        return false;
    }
}
