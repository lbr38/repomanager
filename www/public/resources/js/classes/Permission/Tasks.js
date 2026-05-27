/**
 *  Check user permissions on tasks
 *  Mirrors the backend Controllers\User\Permission\Task class
 */
class TaskPermission {
    /**
     *  Check if the user has any permission on tasks
     *  Admins are allowed to do everything (no 'user_permissions' cookie means the user is an admin,
     *  see Controllers\App\Permissions::load() which deletes the cookie for admins)
     * @returns {boolean}
     */
    allowed()
    {
        if (!mycookie.exists('user_permissions')) {
            return true;
        }

        const userPermissions = JSON.parse(mycookie.get('user_permissions'));

        if (userPermissions.tasks && userPermissions.tasks['allowed-actions'] && userPermissions.tasks['allowed-actions'].length > 0) {
            return true;
        }

        return false;
    }

    /**
     *  Check if the user is allowed to perform a specific action on tasks
     * @param {string} action
     * @returns {boolean}
     */
    allowedAction(action)
    {
        if (!mycookie.exists('user_permissions')) {
            return true;
        }

        const userPermissions = JSON.parse(mycookie.get('user_permissions'));

        if (userPermissions.tasks && userPermissions.tasks['allowed-actions'] && userPermissions.tasks['allowed-actions'].includes(action)) {
            return true;
        }

        return false;
    }
}
