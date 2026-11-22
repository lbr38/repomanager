<div class="reloadable-container" container="settings/users">
    <?php
    if (IS_ADMIN) : ?>
        <div class="settings-section hide" section="users" save="false">
            <div class="group-header hide">
                <div class="flex align-item-center column-gap-10">
                    <img src="/assets/icons/user.svg" class="group-header-icon lowopacity-cst" />
                    <span class="group-header-name">Users</span>
                    <span class="label-count settings-search-count hide"></span>
                </div>
            </div>

            <p class="note settings-section-note">User changes are applied immediately.</p>

            <div id="users-settings-container" class="settings-columns">
                <div class="settings-column">
                    <?php
                    if (!empty($users)) : ?>
                        <div class="div-generic-blue settings-field">
                            <div id="current-users">
                                <h5 class="margin-top-0">CURRENT USERS</h5>

                                <?php
                                foreach ($users as $user) :
                                    if ($user['Role_name'] == 'super-administrator') {
                                        $role = 'Super-administrator';
                                        $roleIcon = 'star';
                                    }
                                    if ($user['Role_name'] == 'administrator') {
                                        $role = 'Administrator';
                                        $roleIcon = 'star';
                                    }
                                    if ($user['Role_name'] == 'usage') {
                                        $role = 'Standard user';
                                        $roleIcon = 'user';
                                    }

                                    // Display full name if available, else display username
                                    if (!empty($user['First_name'])) {
                                        $username = $user['First_name'] . ' ' . $user['Last_name'];
                                    } else {
                                        $username = $user['Username'];
                                    } ?>

                                    <div class="table-container grid-2 bck-blue-alt">
                                        <div>
                                            <div class="flex align-item-center column-gap-8">
                                                <p class="wordbreakall"><?= $username ?></p>
                                                <code class="font-size-9" title="Account type"><?= $user['Type'] ?></code>
                                            </div>
                                            <div class="flex align-item-center lowopacity-cst column-gap-5">
                                                <p>
                                                    <?= $role ?>
                                                </p>
                                                <img src="/assets/icons/<?= $roleIcon ?>.svg" class="icon-np icon-medium" title="<?= $role ?>" />
                                            </div>
                                        </div>

                                        <div class="flex column-gap-15 align-item-center justify-end">
                                            <?php
                                            // Do not print the buttons for the admin account or if $user['Username'] == current user
                                            if ($user['Username'] != 'admin' and $user['Username'] != $_SESSION['username']) :
                                                // Edit permissions button
                                                if ($user['Role_name'] == 'usage') {
                                                    echo '<img src="/assets/icons/build.svg" class="icon-lowopacity user-permissions-edit-btn" user-id="' . $user['Id'] . '" title="Edit permissions of user ' . $user['Username'] . '" />';
                                                }

                                                // Only local accounts can have their password reseted
                                                if ($user['Type'] == 'local') : ?>
                                                    <p class="reset-password-btn" user-id="<?= $user['Id'] ?>" username="<?= $user['Username'] ?>" title="Reset password of user <?= $user['Username'] ?>">
                                                        <img src="/assets/icons/update.svg" class="icon-lowopacity" />
                                                    </p>
                                                    <?php
                                                endif; ?>

                                                <p class="delete-user-btn" user-id="<?= $user['Id'] ?>" username="<?= $user['Username'] ?>" title="Delete user <?= $user['Username'] ?>">
                                                    <img src="/assets/icons/delete.svg" class="icon-lowopacity" />
                                                </p>
                                                <?php
                                            endif ?>
                                        </div>
                                    </div>
                                    <?php
                                endforeach ?>
                            </div>
                        </div>
                        <?php
                    endif ?>
                </div>

                <div class="settings-column">
                    <div class="div-generic-blue settings-field">
                        <h5 class="margin-top-0">CREATE USER</h5>
                        <form id="new-user-form" autocomplete="off">
                            <div class="flex align-item-center column-gap-10">
                                <input type="text" name="username" placeholder="Username" />

                                <select name="role" required>
                                    <option value="usage" selected>Standard user</option>
                                    <option value="administrator">Administrator</option>
                                </select>
                            </div>

                            <button class="btn-small-green margin-top-5" type="submit">Create</button>
                        </form>

                        <div id="user-settings-generated-passwd"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    endif ?>

    <script>
        // Re-apply the settings sections display when this container is reloaded (settings.js is not loaded yet on page load)
        if (typeof settingsLayout !== 'undefined') {
            settingsLayout.apply();
        }
    </script>
</div>
