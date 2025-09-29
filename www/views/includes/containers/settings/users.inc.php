<div class="reloadable-container" container="settings/users">
    <?php
    if (IS_ADMIN) : ?>
        <h3>USERS</h3>

        <div id="users-settings-container" class="div-generic-blue">
            <h6 class="margin-top-0">CREATE USER</h6>
            <form id="new-user-form" autocomplete="off">
                <div class="flex align-item-center column-gap-10">
                    <input type="text" name="username" placeholder="Username" />

                    <select name="role" required>
                        <option value="">Select role...</option>
                        <option value="usage">Standard user</option>
                        <option value="administrator">Administrator</option>
                    </select>

                    <div>
                        <button class="btn-xxsmall-green" type="submit">+</button>
                    </div>
                </div>
            </form>

            <div id="user-settings-generated-passwd"></div>
   
            <?php
            if (!empty($users)) : ?>
                <div id="currentUsers">
                    <h6 class="margin-bottom-5">CURRENT USERS</h6>

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
                <?php
            endif ?>
        </div>
        <?php
    endif ?>
</div>