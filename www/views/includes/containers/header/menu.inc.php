<?php
use \Controllers\User\Permission\Host as HostPermission;
use \Controllers\User\Permission\Task as TaskPermission;

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentSection = explode('/', trim($currentPath, '/'))[0] ?? '';
?>

<nav id="menu" class="reloadable-container" container="header/menu">
    <div id="menu-fixed">
        <div class="flex flex-direction-column row-gap-30">
            <!-- Repositories tab -->
            <div class="menu-item <?= in_array($currentSection, ['']) ? 'menu-item-active' : '' ?>">
                <a href="/">
                    <img src="/assets/icons/package.svg" class="icon" title="Repositories" />
                </a>
            </div>

            <!-- Tasks tab -->
            <?php
            if (TaskPermission::allowed()) : ?>
                <div class="menu-item menu-item-tasks <?= in_array($currentSection, ['run', 'tasks', 'task']) ? 'menu-item-active' : '' ?>">
                    <a href="/tasks">
                        <img src="/assets/icons/rocket.svg" class="icon" title="Tasks" />
                    </a>

                    <?php
                    if ($totalRunningTasks > 0) : ?>
                        <span class="menu-task-badge"><?= $totalRunningTasks ?></span>

                        <div class="menu-task-popup">
                            <?php
                            foreach ($tasksRunning as $task) :
                                $taskParams = json_decode($task['Raw_params'], true); ?>
                                <a href="/run/<?= $task['Id'] ?>" class="menu-task-popup-item">
                                    <span>
                                        <?php
                                        if ($taskParams['action'] == 'create') {
                                            echo $taskParams['repo-type'] == 'local' ? 'New local repo' : 'New mirror repo';
                                        }
                                        if ($taskParams['action'] == 'update') {
                                            echo 'Update';
                                        }
                                        if ($taskParams['action'] == 'env') {
                                            echo 'Point env';
                                        }
                                        if ($taskParams['action'] == 'removeEnv') {
                                            echo 'Remove env';
                                        }
                                        if ($taskParams['action'] == 'rebuild') {
                                            echo 'Rebuild metadata';
                                        }
                                        if ($taskParams['action'] == 'rename') {
                                            echo 'Rename';
                                        }
                                        if ($taskParams['action'] == 'duplicate') {
                                            echo 'Duplicate';
                                        }
                                        if ($taskParams['action'] == 'delete') {
                                            echo 'Delete';
                                        } ?>
                                    </span>
                                    <span class="menu-task-popup-repo"><?= $myTask->getRepo($task['Id']); ?></span>
                                </a>
                                <?php
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

        <div class="flex flex-direction-column row-gap-30">
            <?php
            if (IS_ADMIN) : ?>
                <div class="menu-item <?= $currentSection === 'history' ? 'menu-item-active' : '' ?>">
                    <a href="/history"><img src="/assets/icons/time.svg" class="icon" title="Repomanager history" /></a>
                </div>

                <div class="menu-item <?= $currentSection === 'status' ? 'menu-item-active' : '' ?>">
                    <a href="/status"><img src="/assets/icons/health.svg" class="icon" title="Access system health & monitoring" /></a>
                </div>

                <div class="menu-item <?= $currentSection === 'settings' ? 'menu-item-active' : '' ?>">
                    <a href="/settings"><img src="/assets/icons/cog.svg" class="icon" title="Repomanager settings" /></a>
                </div>
                <?php
            endif ?>
        </div>
    </div>
</nav>
        
