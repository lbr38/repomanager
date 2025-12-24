<?php ob_start(); ?>

<form id="user-permissions-form" user-id="<?= $userId ?>">
    <h5>REPOSITORIES</h5>

    <h6>ALLOW VIEWING OF REPOSITORIES</h6>
    <p class="note">Select the repositories that this user can view.</p>
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
    </select>

    <h6>ALLOW ACTIONS ON REPOSITORIES</h6>
    <p class="note">Select the actions that this user can perform on repositories.</p>
    <select id="user-permissions-repos-actions" user-id="<?= $userId ?>" multiple>
        <option value="create" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('create', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Create repositories</option>
        <option value="update" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('update', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Update repositories</option>
        <option value="delete" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('delete', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Delete repositories</option>
        <option value="duplicate" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('duplicate', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Duplicate repositories</option>
        <option value="rebuild" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('rebuild', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Rebuild repositories</option>
        <option value="edit" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('edit', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Edit repositories</option>
        <option value="browse" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('browse', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Browse repositories</option>
        <option value="upload-package" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('upload-package', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Upload packages to repositories</option> 
        <option value="delete-package" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('delete-package', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Delete packages from repositories</option>
        <option value="env" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('env', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Point environment to repository</option>
        <option value="removeEnv" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('removeEnv', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>Remove repository environment</option>
        <option value="view-stats" <?= isset($permissions['repositories']['allowed-actions']['repos']) && in_array('view-stats', $permissions['repositories']['allowed-actions']['repos']) ? 'selected' : '' ?>>View repository statistics</option>
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
        <option value="request-general-infos" <?= isset($permissions['hosts']['allowed-actions']) && in_array('request-general-infos', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Request general information</option>
        <option value="request-packages-infos" <?= isset($permissions['hosts']['allowed-actions']) && in_array('request-packages-infos', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Request packages information</option>
        <option value="update-packages" <?= isset($permissions['hosts']['allowed-actions']) && in_array('update-packages', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Update packages on hosts</option>
        <option value="reset" <?= isset($permissions['hosts']['allowed-actions']) && in_array('reset', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Reset hosts</option>
        <option value="delete" <?= isset($permissions['hosts']['allowed-actions']) && in_array('delete', $permissions['hosts']['allowed-actions']) ? 'selected' : '' ?>>Delete hosts</option>
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
