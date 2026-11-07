/**
 *  Check user permissions on mirrors
 *  Mirrors the backend Controllers\User\Permission\Mirror class
 */
class MirrorPermission {
    /**
     *  Check if the user is allowed to perform a specific action on mirrors
     *  Admins are allowed to do everything (no 'user_permissions' cookie means the user is an admin,
     *  see Controllers\App\Permissions::load() which deletes the cookie for admins)
     * @param {string} action
     * @returns {boolean}
     */
    allowedAction(action)
    {
        if (!mycookie.exists('user_permissions')) {
            return true;
        }

        const userPermissions = JSON.parse(mycookie.get('user_permissions'));

        if (userPermissions.mirrors && userPermissions.mirrors['allowed-actions'] && userPermissions.mirrors['allowed-actions'].includes(action)) {
            return true;
        }

        return false;
    }
}
