<section class="section-right reloadable-container" container="settings/health">
    
    <h3>HEALTH</h3>

    <div class="div-generic-blue">
        <h6 class="margin-top-0">MAIN DATABASE</h6>
        <p class="note">Storing repositories, users, settings...</p>
        <?php
        $statusError = 0;
        $statusMsg = '';

        /**
         *  Checking that database is readable and writable
         */
        if (!is_readable(DB) or !is_writable(DB)) {
            $statusError++;
            $statusMsg = 'Main database is not readable / writable.';
        } else {
            /**
             *  Checking that all tables are present
             */
            $myconn = new \Models\Connection('main');

            if (!$myconn->checkMainTables()) {
                $statusError++;
                $statusMsg = 'One or more table are missing.';
            }
        }

        if ($statusError == 0) {
            echo '<p><img src="/assets/icons/check.svg" class="icon vertical-align-text-top" /> Healthy</p>';
        } else {
            echo '<p><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> ' . $statusMsg . '</p>';
        }

        if (STATS_ENABLED == "true") : ?>
            <h6>STATS DATABASE</h6>
            <p class="note">Storing repositories access statistics.</p>
            <?php
            $statusError = 0;
            $statusMsg = '';

            if (!file_exists(STATS_DB)) {
                touch(STATS_DB);
            }

            /**
             *  Checking that database is readable and writable
             */
            if (!is_readable(STATS_DB) or !is_writable(STATS_DB)) {
                $statusError++;
                $statusMsg = 'Stats database is not readable / writable.';
            } else {
                /**
                 *  Checking that all tables are present
                 */
                $myconn = new \Models\Connection('stats');

                if (!$myconn->checkStatsTables()) {
                    $statusError++;
                    $statusMsg = 'One or more table are missing.';
                }
            }

            if ($statusError == 0) {
                echo '<p><img src="/assets/icons/check.svg" class="icon vertical-align-text-top" /> Healthy</p>';
            } else {
                echo '<p><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> ' . $statusMsg . '</p>';
            }
        endif;

        if (MANAGE_HOSTS == "true") : ?>
            <h6>HOSTS DATABASE</h6>
            <p class="note">Storing hosts and their information.</p>
            <?php
            $statusError = 0;
            $statusMsg = '';

            if (!file_exists(HOSTS_DB)) {
                touch(HOSTS_DB);
            }

            /**
             *  Checking that database is readable and writable
             */
            if (!is_readable(HOSTS_DB) or !is_writable(HOSTS_DB)) {
                $statusError++;
                $statusMsg = 'Hosts database is not readable / writable.';
            } else {
                /**
                 *  Checking that all tables are present
                 */
                $myconn = new \Models\Connection('hosts');

                if (!$myconn->checkHostsTables()) {
                    $statusError++;
                    $statusMsg = 'One or more table are missing.';
                }
            }

            if ($statusError == 0) {
                echo '<p><img src="/assets/icons/check.svg" class="icon vertical-align-text-top" /> Healthy</p>';
            } else {
                echo '<p><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> ' . $statusMsg . '</p>';
            }
        endif ?>

        <h6>REPOMANAGER SERVICE</h6>
        <p class="note">Repomanager service is used to execute regular tasks such as executing scheduled tasks, sending scheduled tasks reminders, logging repositories access...</p>
        <?php
        if (SERVICE_RUNNING) {
            echo '<p><img src="/assets/icons/check.svg" class="icon vertical-align-text-top" /> Running</p>';
        } else {
            echo '<p><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /> Service is not running</p>';
        } ?>
    </div>

    <?php
    /**
     *  Those sections is only accessible to super-administrator user
     */
    if (IS_ADMIN) : ?>
        <h3>USERS</h3>

        <div id="users-settings-container" class="div-generic-blue">
            <h6 class="margin-top-0">CREATE USER</h6>
            <form id="new-user-form" autocomplete="off">
                <div class="flex align-item-center column-gap-10">
                    <input type="text" name="username" placeholder="Username" />

                    <select name="role" required>
                        <option value="">Select role...</option>
                        <option value="usage">usage (read-only)</option>
                        <option value="administrator">administrator</option>
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
                            $role = 'Read-only';
                            $roleIcon = 'view';
                        } ?>

                        <div class="table-container grid-2 bck-blue-alt">
                            <div>
                                <div class="flex align-item-center column-gap-8">
                                    <p><?= $user['Username'] ?></p>
                                    <code class="font-size-9" title="Account type"><?= $user['Type'] ?></code>
                                </div>
                                <div class="flex align-item-center lowopacity-cst column-gap-5">
                                    <p>
                                        <?= $role ?>
                                    </p>
                                    <img src="/assets/icons/<?= $roleIcon ?>.svg" class="icon-np icon-medium" title="<?= $role ?>" />
                                </div>
                            </div>

                            <div class="flex column-gap-10 justify-end">
                                <?php
                                // Do not print the buttons for the admin account or if $user['Username'] == current user
                                if ($user['Username'] != 'admin' and $user['Username'] != $_SESSION['username']) :
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

        <div>
            <h3>DEBUG MODE</h3>
            <div class="div-generic-blue">
                <h6 class="margin-top-0">ENABLE DEBUG MODE</h6>
                <p class="note">Debug mode will display additional information on the interface.</p>

                <label class="onoff-switch-label">
                    <input id="debug-mode-btn" class="onoff-switch-input" type="checkbox" value="true" <?php echo (DEBUG_MODE == "true") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>
            </div>
        </div>

        <?php
        if (DEBUG_MODE == 'true') : ?>
            <div>
                <h3>LOGS</h3>
                <div class="div-generic-blue">
                    <h6 class="margin-top-0">WEBSOCKET SERVER LOGS</h6>
                    <?php
                    // Get all log file names
                    $logFiles = glob(WS_LOGS_DIR . '/*.log');

                    if (empty($logFiles)) {
                        echo '<p class="note">No logs for now.</p>';
                    }

                    if (!empty($logFiles)) :
                        // Sort logs in reverse order
                        rsort($logFiles); ?>

                        <p class="note">Select a log file to view.</p>
                        <div class="flex align-item-center column-gap-10">
                            <select id="websocket-log-select">
                                <?php
                                foreach ($logFiles as $logFile) {
                                    echo '<option value="' . basename($logFile) . '">' . basename($logFile) . '</option>';
                                } ?>
                            </select>
                            <div>
                                <button type="button" id="websocket-log-btn" class="btn-xsmall-green">View</button>
                            </div>
                        </div>
                        <?php
                    endif ?>
                </div>
            </div>
            <?php
        endif;
    endif ?>
</section>