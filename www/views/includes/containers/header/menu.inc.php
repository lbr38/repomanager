<?php
use \Controllers\User\Permission\Host as HostPermission;
use \Controllers\User\Permission\Task as TaskPermission;

$currentSection = '';

if (defined('__ACTUAL_URI__') && !empty(__ACTUAL_URI__[1])) {
    $currentSection = __ACTUAL_URI__[1];
} ?>

<nav id="menu" class="reloadable-container" container="header/menu">
    <div id="menu-fixed">
        <div class="flex flex-direction-column row-gap-30">
            <!-- Repositories tab -->
            <div class="menu-item <?= in_array($currentSection, ['', 'repository', 'snapshot', 'stats']) ? 'menu-item-active' : '' ?>">
                <a href="/">
                    <img src="/assets/icons/package.svg" class="icon" title="Repositories" />
                </a>
            </div>

            <!-- Tasks tab -->
            <?php
            if (TaskPermission::allowed()) : ?>
                <div class="menu-item menu-item-tasks <?= in_array($currentSection, ['tasks', 'task']) ? 'menu-item-active' : '' ?>">
                    <a href="/tasks">
                        <img src="/assets/icons/rocket.svg" class="icon" title="Tasks" />
                    </a>

                    <?php
                    if ($totalRunningTasks > 0) : ?>
                        <span class="menu-task-badge"><?= $totalRunningTasks ?></span>

                        <div class="menu-task-popup">
                            <?php
                            foreach ($tasksRunning as $task) :
                                $color = 'white';
                                $taskParams = json_decode($task['Raw_params'], true);
                                $repo = $taskController->getRepo($task['Id'], false);

                                if ($repo['type'] == 'deb') {
                                    $color = 'red';
                                }

                                if ($repo['type'] == 'rpm') {
                                    $color = 'blue';
                                } ?>

                                <div class="menu-task-popup-item">
                                    <a href="/task/<?= $task['Id'] ?>" class="menu-task-popup-item-link">
                                        <span><?= $taskController::generateLiteralAction($task)['title']; ?></span>
                                        <span class="label-<?= $color ?>"><?= $taskController->getRepo($task['Id']); ?></span>
                                    </a>

                                    <?php
                                    // Check if the user has permission to stop tasks
                                    if (TaskPermission::allowedAction('stop')) : ?>
                                        <span title="Stop task" class="stop-task-btn" task-id="<?= $task['Id'] ?>"><img src="/assets/icons/stop.svg" class="icon-medium icon-lowopacity" /></span>
                                        <?php
                                    endif ?>
                                </div>
                                <?php
                                unset($taskParams, $repo, $color);
                            endforeach ?>
                        </div>
                        <?php
                    endif ?>
                </div>
                <?php
            endif ?>

            <!-- Hosts tab -->
            <?php
            if (HostPermission::allowed()) : ?>
                <div class="menu-item <?= in_array($currentSection, ['hosts', 'host']) ? 'menu-item-active' : '' ?>">
                    <a href="/hosts">
                        <img src="/assets/icons/server.svg" class="icon" title="Hosts" />
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="flex flex-direction-column row-gap-20">
            <?php
            if (IS_ADMIN) : ?>
                <div class="menu-sub-item <?= $currentSection === 'settings' ? 'menu-item-active' : '' ?>">
                    <a href="/settings"><img src="/assets/icons/cog.svg" class="icon" title="Repomanager settings" /></a>
                </div>

                <div class="menu-sub-item <?= $currentSection === 'history' ? 'menu-item-active' : '' ?>">
                    <a href="/history"><img src="/assets/icons/time.svg" class="icon" title="Repomanager history" /></a>
                </div>

                <div class="menu-sub-item <?= $currentSection === 'status' ? 'menu-item-active' : '' ?>">
                    <a href="/status"><img src="/assets/icons/health.svg" class="icon" title="System health & monitoring" /></a>
                </div>            
                <?php
            endif ?>

            <div class="menu-sub-item get-panel-btn pointer relative" panel="general/notification" title="Show notifications">
                <img src="/assets/icons/alarm.svg" class="icon-lowopacity" />
                <?php
                if (NOTIFICATION != 0) : ?>
                    <span id="notification-count"><?= NOTIFICATION ?></span>
                    <?php
                endif ?>
            </div>

            <div class="menu-sub-item get-panel-btn pointer" panel="general/userspace" title="Userspace">
                <img src="/assets/icons/user.svg" class="icon" />
            </div>
        </div>
    </div>
</nav>
        
