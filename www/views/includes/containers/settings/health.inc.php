<section class="section-right reloadable-container" container="settings/health">
    
    <h3>HEALTH</h3>

    <div class="div-generic-blue">
        <h6 class="margin-top-0">MAIN DATABASE</h6>
        <p class="note">Main database. Repomanager cannot run if this database is on error.</p>
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
            echo '<p><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" />' . $statusMsg . '</p>';
        }

        if (STATS_ENABLED == "true") : ?>
            <h6>STATS DATABASE</h6>
            <p class="note">Repositories statistics database.</p>
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
                echo '<p><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" />' . $statusMsg . '</p>';
            }
        endif;

        if (MANAGE_HOSTS == "true") : ?>
            <h6>HOSTS DATABASE</h6>
            <p class="note">Hosts database.</p>
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
                echo '<p><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" />' . $statusMsg . '</p>';
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
    if (IS_SUPERADMIN) : ?>
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
                    <h6>CURRENT USERS</h6>

                    <?php
                    foreach ($users as $user) : ?>
                        <div class="table-container grid-3 bck-blue-alt">
                            <div>
                                <p><?= $user['Username'] ?></p>
                                <p class="lowopacity-cst">
                                    <?php
                                    if ($user['Type'] == 'local') {
                                        echo 'Local account';
                                    } ?>
                                </p>
                            </div>

                            <p><?= $user['Role_name'] ?></p>

                            <div class="flex column-gap-10 justify-end">
                                <?php
                                if ($user['Username'] != 'admin') : ?>
                                    <p class="reset-password-btn" user-id="<?= $user['Id'] ?>" username="<?= $user['Username'] ?>" title="Reset password of user <?= $user['Username'] ?>">
                                        <img src="/assets/icons/update.svg" class="icon-lowopacity" />
                                    </p>
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
            <h3>LOGS</h3>
            <div class="div-generic-blue">
                <h6 class="margin-top-0">WEBSOCKET SERVER LOGS</h6>
                <?php
                // Get all log file names
                $logFiles = glob(WS_LOGS_DIR . '/*.log');

                if (empty($logFiles)) {
                    echo '<p class="note">No logs for now.</p>';
                }

                if (!empty($logFiles)) : ?>
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
    endif ?>
</section>