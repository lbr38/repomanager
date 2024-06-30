<header class="reloadable-container" container="header/menu">
    <nav id="menu">
        <div>
            <div id="title">
                <a href="/"><span>Repomanager</span></a>
            </div>

            <?php
            /**
             *  REPOS tab
             */
            if (__ACTUAL_URI__[1] == '' or __ACTUAL_URI__[1] == 'browse' or __ACTUAL_URI__[1] == 'stats') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div class="<?= $headerMenuClass ?>">
                <a href="/">
                    <div class="flex align-item-center column-gap-3">
                        <img src="/assets/icons/menu.svg" class="icon" />
                        <span class="menu-section-title">REPOS</span>
                    </div>
                </a>
            </div>

            <?php
            /**
             *  MANAGE HOSTS tab
             */
            if (__ACTUAL_URI__[1] == 'hosts' or __ACTUAL_URI__[1] == 'host') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (MANAGE_HOSTS == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/hosts">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/server.svg" class="icon" />
                            <span class="menu-section-title">MANAGE HOSTS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

            /**
             *  MANAGE PROFILES tab
             */
            if (__ACTUAL_URI__[1] == 'profiles') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (IS_ADMIN and MANAGE_PROFILES == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/profiles">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/stack.svg" class="icon" />
                            <span class="menu-section-title">MANAGE PROFILES</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

            /**
             *  SETTINGS tab
             */
            if (__ACTUAL_URI__[1] == 'settings') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (IS_ADMIN) : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/settings">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/settings.svg" class="icon" />
                            <span class="menu-section-title">SETTINGS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

            /**
             *  TASKS tab
             */
            if (__ACTUAL_URI__[1] == 'run') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div id="header-refresh-container" class="<?= $headerMenuClass ?>">
                <div>
                    <a href="/run?task-log=latest">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/rocket.svg" class="icon" />
                            <span class="menu-section-title">TASKS</span>
                        </div>
                    </a>

                    <div id="header-refresh">
                        <?php
                        /**
                         *  Print a notification badge according to the number of running tasks
                         */
                        if ($totalRunningTasks > 0) {
                            echo '<span class="op-total-running">' . $totalRunningTasks . '</span>';
                        }

                        /**
                         *  If at least 1 task is running then we display its details
                         */
                        if ($totalRunningTasks > 0) :
                            echo '<div class="header-op-container">';
                            /**
                             *  Print each running task
                             */
                            foreach ($tasksRunning as $task) :
                                $taskParams = json_decode($task['Raw_params'], true); ?>

                                <div class="header-op-subdiv btn-large-red">
                                    <a href="/run?task-log=<?= $task['Logfile'] ?>">
                                        <span>
                                            <?php
                                            if ($taskParams['action'] == 'create') {
                                                if ($taskParams['repo-type'] == 'local') {
                                                    echo 'New local repository ';
                                                }
                                                if ($taskParams['repo-type'] == 'mirror') {
                                                    echo 'New mirror repository ';
                                                }
                                            }
                                            if ($taskParams['action'] == 'update') {
                                                echo 'Update ';
                                            }
                                            if ($taskParams['action'] == 'env') {
                                                echo 'Point environment ';
                                            }
                                            if ($taskParams['action'] == 'removeEnv') {
                                                echo 'Remove environment ';
                                            }
                                            if ($taskParams['action'] == 'rebuild') {
                                                echo 'Rebuilding metadata ';
                                            }
                                            if ($taskParams['action'] == 'duplicate') {
                                                echo 'Duplicate ';
                                            }
                                            if ($taskParams['action'] == 'delete') {
                                                echo 'Delete ';
                                            } ?>
                                        </span>

                                        <span class="label-white"><?= $myTask->getRepo($task['Id']); ?></span>
                                    </a>

                                    <span title="Stop task" class="stop-task-btn" pid="<?= $task['Pid'] ?>">
                                        <img src="/assets/icons/delete.svg" class="icon">
                                    </span>
                                </div>
                                <?php
                            endforeach;
                            echo '</div>';

                            unset($tasksRunning);
                        endif ?>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div>
                <div class="flex column-gap-5 align-item-center">
                    <span class="round-item bkg-<?= $currentLoadColor ?>"></span>
                    <span class="lowopacity-cst font-size-11">CPU load: <?= $currentLoad ?></span>
                </div>
            </div>

            <div class="menu-sub-container relative">
                <img src="/assets/icons/alarm.svg" class="icon-lowopacity slide-panel-btn" slide-panel="general/notification" title="Show notifications" />
                <?php
                if (NOTIFICATION != 0) : ?>
                    <span id="notification-count"><?= NOTIFICATION ?></span>
                    <?php
                endif ?>
            </div>

            <?php
            /**
             *  History tab
             */
            if (IS_ADMIN) {
                echo '<a href="/history"><img src="/assets/icons/time.svg" class="icon-lowopacity" title="History" /></a>';
            } ?>

            <div class="<?= $headerMenuClass ?>">
                <div class="flex align-item-center column-gap-3 slide-panel-btn lowopacity pointer" slide-panel="general/userspace" title="Userspace">
                    <img src="/assets/icons/user.svg" class="icon" />
                    <span class="menu-section-title">
                        <?php
                        echo $_SESSION['username'];

                        if (!empty($_SESSION['first_name']) and !empty($_SESSION['last_name'])) {
                            echo ' (' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] . ')';
                        } elseif (!empty($_SESSION['first_name'])) {
                            echo ' (' . $_SESSION['first_name'] . ')';
                        } ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>
</header>