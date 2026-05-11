<?php
use \Controllers\User\Permission\Repo as RepoPermission; ?>

<section class="section-main reloadable-container" container="repos/list">
    <div id="repositories-list">
        <h3 class="margin-bottom-40">REPOSITORIES</h3>

        <div class="flex flex-wrap align-item-center column-gap-10 row-gap-15 margin-bottom-10 kpi-container">
            <div class="kpi-card">
                <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value"><?= $totalRepos ?></p>
                    <p class="mediumopacity-cst"><?= $totalRepos <= 1 ? 'Repository' : 'Repositories' ?></p>
                </div>
            </div>

            <div class="kpi-card">
                <img src="/assets/icons/disk.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value"><?= $diskUsedSpacePercent ?>%</p>
                    <p class="mediumopacity-cst">Used storage</p>
                </div>
            </div>

            <div class="div-generic-blue storage-card margin-bottom-0 min-width-200">
                <div class="flex justify-space-between align-item-center margin-bottom-10">
                    <h6 class="margin-top-0">STORAGE</h6>
                    <p class="mediumopacity-cst"><?= $diskUsedSpaceHuman ?> / <?= $diskFreeSpaceHuman ?> free</p>
                </div>

                <div class="storage-meter" title="<?= $diskUsedSpacePercent ?>% used storage">
                    <span style="width: <?= $diskUsedSpacePercent ?>%"></span>
                </div>
            </div>

            <?php
            // KPI card: last scheduled task status
            if (!empty($lastScheduledTask) and !empty($lastScheduledTask['Date']) and !empty($lastScheduledTask['Time']) and (time() - strtotime($lastScheduledTask['Date'] . ' ' . $lastScheduledTask['Time']) <= 1296000)) :
                if ($lastScheduledTask['Status'] == 'error' or $lastScheduledTask['Status'] == 'stopped') {
                    $lastTaskIcon = 'warning-red';
                    $lastTaskValue = 'Failed';
                } else {
                    $lastTaskIcon = 'check';
                    $lastTaskValue = 'Success';
                } ?>
                <a href="/task/<?= $lastScheduledTask['Id'] ?>" class="kpi-card">
                    <img src="/assets/icons/<?= $lastTaskIcon ?>.svg" class="icon-np icon-medium" />
                    <div>
                        <p class="kpi-value"><?= $lastTaskValue ?></p>
                        <p class="mediumopacity-cst">Last scheduled task</p>
                    </div>
                </a>
                <?php
            endif;

            // KPI card: next scheduled task
            if (!empty($nextScheduledTasks)) : ?>
                <a href="/tasks" class="kpi-card">
                    <img src="/assets/icons/time.svg" class="icon-np icon-medium" />
                    <div>
                        <p class="kpi-value"><?php
                        if ($nextScheduledTasks[0]['left']['days'] > 0) {
                            echo $nextScheduledTasks[0]['left']['days'] . ' day' . ($nextScheduledTasks[0]['left']['days'] > 1 ? 's' : '');
                        } else {
                            echo $nextScheduledTasks[0]['left']['time'];
                        } ?></p>
                        <p class="mediumopacity-cst">Next scheduled task</p>
                    </div>
                </a>
                <?php
            endif ?>
        </div>

        <?php
        // Print repositories
        if (IS_ADMIN or (!empty(USER_PERMISSIONS['repositories']['view']['groups']) or in_array('all', USER_PERMISSIONS['repositories']['view']))) { ?>
            <div class="flex flex-wrap align-item-center column-gap-10 row-gap-10 margin-bottom-15 repo-toolbar">
                <input id="repo-search-input" class="flex-grow margin-bottom-0" type="text" placeholder="Search" onkeyup="myrepo.search()" title="Search by repository name, distribution, section or release version" />

                <div class="flex align-item-center column-gap-5">
                    <?php
                    if (RepoPermission::allowedAction('edit-groups')) : ?>
                        <div class="slide-btn get-panel-btn mediumopacity" panel="repos/groups/list" title="Manage repos groups">
                            <img src="/assets/icons/folder.svg" />
                            <span>Groups</span>
                        </div>
                        <?php
                    endif;

                    if (RepoPermission::allowedAction('edit-source')) : ?>
                        <div class="slide-btn get-panel-btn mediumopacity" panel="repos/sources/list" title="Manage source repositories">
                            <img src="/assets/icons/internet.svg" />
                            <span>Source repositories</span>
                        </div>
                        <?php
                    endif;

                    if (RepoPermission::allowedAction('create')) : ?>
                        <div class="slide-btn get-panel-btn bkg-green" panel="repos/new" title="Create a new mirror or local repository">
                            <img src="/assets/icons/plus.svg" />
                            <span>Create a new repository</span>
                        </div>
                        <?php
                    endif ?>

                    <div id="hide-all-repo-groups" state="visible">
                        <img src="/assets/icons/view.svg" class="icon lowopacity pointer" title="Hide/Show all repositories groups" />
                    </div>
                </div>
            </div>

            <div id="repos-list-container">
                <?php include_once(ROOT . '/views/includes/repos-list.inc.php'); ?>
            </div>
            <?php
        } else {
            echo '<p class="note">Nothing to show here!</p>';
        } ?>
    </div>
</section>