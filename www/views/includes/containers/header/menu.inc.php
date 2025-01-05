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
                    <div class="flex align-item-center column-gap-10">
                        <img src="/assets/icons/package.svg" class="icon" />
                        <span class="menu-section-title">REPOSITORIES</span>
                    </div>
                </a>
            </div>

            <?php
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
                    <a href="/run">
                        <div class="flex align-item-center column-gap-10">
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
                                    <a href="/run/<?= $task['Id'] ?>">
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

                                    <span title="Stop task" class="stop-task-btn" task-id="<?= $task['Id'] ?>">
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

            <?php
            /**
             *  HOSTS tab
             */
            if (__ACTUAL_URI__[1] == 'hosts' or __ACTUAL_URI__[1] == 'host') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (MANAGE_HOSTS == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/hosts">
                        <div class="flex align-item-center column-gap-10">
                            <img src="/assets/icons/server.svg" class="icon" />
                            <span class="menu-section-title">HOSTS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="flex align-item-center column-gap-30 margin-right-15">
            <?php
            if (IS_ADMIN) : ?>
                <div>
                    <a href="/settings"><img src="/assets/icons/cog.svg" class="icon-lowopacity" title="Repomanager settings" /></a>
                </div>

                <div>
                    <a href="/history"><img src="/assets/icons/time.svg" class="icon-lowopacity" title="Repomanager history" /></a>
                </div>
                <?php
            endif ?>

            <div class="flex column-gap-8 align-item-center" title="CPU load">
                <img src="/assets/icons/cpu.svg" class="lowopacity-cst icon-np" />
                <span class="lowopacity-cst font-size-12"><?= $currentLoad ?></span>
                <span class="round-item bkg-<?= $currentLoadColor ?>"></span>
            </div>

            <div class="relative">
                <img src="/assets/icons/alarm.svg" class="icon-lowopacity get-panel-btn" panel="general/notification" title="Show notifications" />
                <?php
                if (NOTIFICATION != 0) : ?>
                    <span id="notification-count"><?= NOTIFICATION ?></span>
                    <?php
                endif ?>
            </div>

            <div class="flex align-item-center column-gap-10 get-panel-btn lowopacity pointer" panel="general/userspace" title="Userspace">
                <img src="/assets/icons/user.svg" class="icon" />
                <span>
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
    </nav>
</header>