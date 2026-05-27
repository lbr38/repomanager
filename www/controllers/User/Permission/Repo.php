<?php

namespace Controllers\User\Permission;

class Repo
{
    /**
     *  Check if the user is allowed to perform a specific action on repositories
     */
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

    /**
     *  Check if the user is allowed to view a specific repository
     */
    public static function allowedToView(int $repoId) : bool
    {
        // Admins are allowed to view everything
        if (IS_ADMIN) {
            return true;
        }

        if (in_array('all', USER_PERMISSIONS['repositories']['view'])) {
            return true;
        }

        // The repository can be granted individually
        if (in_array($repoId, USER_PERMISSIONS['repositories']['view']['repos'])) {
            return true;
        }

        if (empty(USER_PERMISSIONS['repositories']['view']['groups'])) {
            return false;
        }

        $groupController = new \Controllers\Group\Repo();
        $groupsIds = $groupController->getRepoGroupsIds($repoId);

        // Repositories that are member of no group belong to the fictitious 'Default' group, which Id is 0
        if (empty($groupsIds)) {
            $groupsIds = [0];
        }

        // Or granted through one of the groups it is member of
        foreach ($groupsIds as $groupId) {
            if (in_array($groupId, USER_PERMISSIONS['repositories']['view']['groups'])) {
                return true;
            }
        }

        return false;
    }
}
