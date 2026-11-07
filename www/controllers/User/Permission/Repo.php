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
     *  Check if the user is allowed to view a specific mirror
     *  Same rules as repositories: all repositories, the group of the mirror, or the mirror granted individually
     */
    public static function allowedToViewMirror(int $mirrorId) : bool
    {
        // Admins are allowed to view everything
        if (IS_ADMIN) {
            return true;
        }

        if (in_array('all', USER_PERMISSIONS['repositories']['view'])) {
            return true;
        }

        // The mirror can be granted individually (permissions saved before mirrors existed have no 'mirrors' key)
        if (in_array($mirrorId, USER_PERMISSIONS['repositories']['view']['mirrors'] ?? [])) {
            return true;
        }

        // Mirrors that are member of no group belong to the fictitious 'Default' group, which Id is 0
        $groupId = (new \Controllers\Mirror\Mirror())->getGroupsIds()[$mirrorId] ?? 0;

        return in_array($groupId, USER_PERMISSIONS['repositories']['view']['groups'] ?? []);
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
