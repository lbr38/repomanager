<?php

namespace Controllers\User\Permission;

class Repo
{
    public static function allowedAction(string $action) : bool
    {
        if (isset(USER_PERMISSIONS['repositories']['allowed-actions']['repos']) && in_array($action, USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) {
            return true;
        }

        return false;
    }
}
