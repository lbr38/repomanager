<?php ob_start(); ?>

<form id="user-permissions-form" user-id="<?= $userId ?>">
    <h5>REPOSITORIES</h5>

    <h6>ALLOW VIEWING OF REPOSITORIES</h6>
    <p class="note">Select the repositories (curated, local and mirrors) or groups that this user can view.</p>
    <select id="user-permissions-repos-view" user-id="<?= $userId ?>" multiple>
        <option value="all" <?= isset($permissions['repositories']['view']) && in_array('all', $permissions['repositories']['view']) ? 'selected' : '' ?>>All repositories</option>

        <optgroup label="Groups">
            <?php
            // Display groups and select them if they are in the permissions
            foreach ($groupsList as $group) {
                $selected = '';

                // If the group Id is a key in the permissions, select it
                if (in_array($group['Id'], $permissions['repositories']['view']['groups'])) {
                    $selected = 'selected';
                }

                echo '<option value="group-' . $group['Id'] . '" ' . $selected . '>Group ' . $group['Name'] . '</option>';
            } ?>
        </optgroup>

        <optgroup label="Curated &amp; local repositories">
            <?php
            // Display repositories and select them if they are in the permissions
            foreach ($reposList as $repoId => $repoLabel) {
                $selected = '';

                if (in_array($repoId, $permissions['repositories']['view']['repos'])) {
                    $selected = 'selected';
                }

                echo '<option value="repo-' . $repoId . '" ' . $selected . '>' . $repoLabel . '</option>';
            } ?>
        </optgroup>

        <optgroup label="Mirrors">
            <?php
            // Display mirrors and select them if they are in the permissions (permissions saved before mirrors existed have no 'mirrors' key)
            foreach ($mirrorsList as $mirrorId => $mirrorLabel) {
                $selected = in_array($mirrorId, $permissions['repositories']['view']['mirrors'] ?? []) ? 'selected' : '';

                echo '<option value="mirror-' . $mirrorId . '" ' . $selected . '>' . htmlspecialchars($mirrorLabel) . '</option>';
            } ?>
        </optgroup>
    </select>

    <h6>ALLOW ACTIONS ON REPOSITORIES</h6>
    <p class="note">Select the actions that this user can perform on repositories.</p>
    <?php
    // Actions are stored separately for repositories and mirrors, mirrors actions are prefixed with 'mirror-' in the selector (see events/settings/user.js)
    $reposActions = [
        'All repositories' => [
            'edit-source' => 'Edit source repositories',
            'edit-groups' => 'Edit repository groups'
        ],
        'Curated & local repositories' => [
            'create' => 'Create repositories',
            'update' => 'Update repositories',
            'delete' => 'Delete repositories',
            'duplicate' => 'Duplicate repositories',
            'rebuild' => 'Rebuild repositories',
            'rename' => 'Rename repositories',
            'edit' => 'Edit repositories',
            'browse' => 'Browse repositories',
            'upload-package' => 'Upload packages to repositories',
            'delete-package' => 'Delete packages from repositories',
            'env' => 'Point environment to repository',
            'removeEnv' => 'Remove repository environment',
            'view-stats' => 'View repository statistics'
        ]
    ];

    $mirrorsActions = [
        'create' => 'Create mirrors',
        'update' => 'Update (synchronize) mirrors',
        'edit' => 'Edit mirrors',
        'delete' => 'Delete mirrors',
        'env' => 'Point environment to mirror',
        'removeEnv' => 'Remove mirror environment'
    ]; ?>
    <select id="user-permissions-repos-actions" user-id="<?= $userId ?>" multiple>
        <?php
        foreach ($reposActions as $groupLabel => $actions) : ?>
            <optgroup label="<?= htmlspecialchars($groupLabel) ?>">
                <?php
                foreach ($actions as $action => $label) {
                    $selected = in_array($action, $permissions['repositories']['allowed-actions'] ?? []) ? 'selected' : '';
                    echo '<option value="' . $action . '" ' . $selected . '>' . $label . '</option>';
                } ?>
            </optgroup>
            <?php
        endforeach ?>

        <optgroup label="Mirrors">
            <?php
            foreach ($mirrorsActions as $action => $label) {
                $selected = in_array($action, $permissions['mirrors']['allowed-actions'] ?? []) ? 'selected' : '';
                echo '<option value="mirror-' . $action . '" ' . $selected . '>' . $label . '</option>';
            } ?>
        </optgroup>
    </select>

    <h5>TASKS</h5>

    <h6>ALLOW ACTIONS ON TASKS</h6>
    <p class="note">Select the tasks actions that this user can perform.</p>
    <select id="user-permissions-tasks-actions" user-id="<?= $userId ?>" multiple>
        <option value="relaunch" <?= isset($permissions['tasks']['allowed-actions']) && in_array('relaunch', $permissions['tasks']['allowed-actions']) ? 'selected' : '' ?>>Relaunch tasks</option>
        <option value="delete" <?= isset($permissions['tasks']['allowed-actions']) && in_array('delete', $permissions['tasks']['allowed-actions']) ? 'selected' : '' ?>>Cancel and delete tasks</option>
        <option value="enable" <?= isset($permissions['tasks']['allowed-actions']) && in_array('enable', $permissions['tasks']['allowed-actions']) ? 'selected' : '' ?>>Enable tasks</option>
        <option value="disable" <?= isset($permissions['tasks']['allowed-actions']) && in_array('disable', $permissions['tasks']['allowed-actions']) ? 'selected' : '' ?>>Disable tasks</option>
        <option value="stop" <?= isset($permissions['tasks']['allowed-actions']) && in_array('stop', $permissions['tasks']['allowed-actions']) ? 'selected' : '' ?>>Stop tasks</option>
    </select>

    <h5>HOSTS</h5>

    <h6>ALLOW ACTIONS ON HOSTS</h6>
    <select id="user-permissions-hosts-actions" user-id="<?= $userId ?>" multiple>
        <option value="register" <?= isset($permissions['hosts']['allowed-actions']) && in_array('register', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Register hosts (API key)</option>
        <option value="request-general-infos" <?= isset($permissions['hosts']['allowed-actions']) && in_array('request-general-infos', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Request general information</option>
        <option value="request-packages-infos" <?= isset($permissions['hosts']['allowed-actions']) && in_array('request-packages-infos', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Request package information</option>
        <option value="update-packages" <?= isset($permissions['hosts']['allowed-actions']) && in_array('update-packages', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Update packages on hosts</option>
        <option value="reset" <?= isset($permissions['hosts']['allowed-actions']) && in_array('reset', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Reset hosts</option>
        <option value="delete" <?= isset($permissions['hosts']['allowed-actions']) && in_array('delete', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Delete hosts</option>
        <option value="edit-profiles" <?= isset($permissions['hosts']['allowed-actions']) && in_array('edit-profiles', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Edit host profiles</option>
        <option value="edit-groups" <?= isset($permissions['hosts']['allowed-actions']) && in_array('edit-groups', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Edit host groups</option>
        <option value="edit-settings" <?= isset($permissions['hosts']['allowed-actions']) && in_array('edit-settings', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Edit display settings</option>
    </select>

    <br><br>
    <button type="submit" class="btn-small-green">Save</button>
</form>

<script>
$(document).ready(function(){
    myselect2.convert('#user-permissions-repos-actions', 'Select allowed actions...');
    myselect2.convert('#user-permissions-repos-view', 'Select repositories...');
    myselect2.convert('#user-permissions-tasks-actions', 'Select allowed actions...');
    myselect2.convert('#user-permissions-hosts-actions', 'Select allowed actions...');
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'settings/user/permissions';
$slidePanelTitle = 'EDIT USER PERMISSIONS';

include(ROOT . '/views/includes/slide-panel.inc.php');
