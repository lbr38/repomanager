<?php

namespace Controllers\User\Permission;

class Host
{
    public static function allowedAction(string $action) : bool
    {
        if (isset(USER_PERMISSIONS['hosts']['allowed-actions']) && in_array($action, USER_PERMISSIONS['hosts']['allowed-actions'])) {
            return true;
        }

        return false;
    }
}
