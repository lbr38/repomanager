<?php
use \Controllers\User\Permission\Repo as RepoPermission; ?>

<section class="section-main reloadable-container" container="repos/list">
    <div class="reposList">
        <h3 class="margin-bottom-40">REPOSITORIES</h3>

        <div class="flex align-item-center justify-space-between column-gap-10 margin-bottom-10">
            <div class="flex flex-wrap align-item-center column-gap-10 row-gap-5">
                <div class="flex align-item-center column-gap-5 mediumopacity-cst" title="Total repositories">
                    <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
                    <p class="font-size-14"><?= $totalRepos ?></p>
                </div>

                <p class="mediumopacity-cst">●</p>

                <div class="flex align-item-center column-gap-15" title="Used storage: <?= $diskUsedSpaceHuman ?> / Free storage: <?= $diskFreeSpaceHuman ?>">
                    <div class="flex align-item-center column-gap-6">
                        <div class="echart-container">
                            <div id="repo-storage-chart" class="echart"></div>
                        </div>

                        <p class="font-size-14 mediumopacity-cst"><?= $diskUsedSpacePercent ?>% used storage</p>
                    </div>
                </div>

                <?php
                if (!empty($lastScheduledTask) and !empty($lastScheduledTask['Date']) and !empty($lastScheduledTask['Time'])) :
                    if ($lastScheduledTask['Status'] == 'error' or $lastScheduledTask['Status'] == 'stopped') {
                        $icon = 'warning-red';
                        $message = 'Last sched. task failed';
                    } else {
                        $icon = 'check';
                        $message = 'Last sched. task successful';
                    } ?>

                    <p class="mediumopacity-cst">●</p>

                    <div class="flex align-item-center column-gap-5" title="Last scheduled task: <?= DateTime::createFromFormat('Y-m-d', $lastScheduledTask['Date'])->format('d-m-Y') . ' ' . $lastScheduledTask['Time'] ?>">
                        <img src="/assets/icons/<?= $icon ?>.svg" class="icon-np icon-medium" />

                        <p class="mediumopacity">
                            <a href="/run/<?= $lastScheduledTask['Id'] ?>"><?= $message ?></a>
                        </p>
                    </div>
                    <?php
                endif;

                if (!empty($nextScheduledTasks)) : ?>
                    <p class="mediumopacity-cst">●</p>

                    <div class="flex align-item-center column-gap-5" title="Next scheduled task: <?= DateTime::createFromFormat('Y-m-d', $nextScheduledTasks[0]['date'])->format('d-m-Y') . ' ' . $nextScheduledTasks[0]['time'] ?>">
                        <img src="/assets/icons/time.svg" class="icon-np icon-medium mediumopacity-cst" />

                        <p class="mediumopacity">
                            <a href="/run">
                            <?php
                            // If days left = 0 (current day) then print hours left instead
                            if ($nextScheduledTasks[0]['left']['days'] > 0) {
                                echo $nextScheduledTasks[0]['left']['days'] . ' days left until next sched. task';
                            } else {
                                echo $nextScheduledTasks[0]['left']['time'] . ' left until next sched. task';
                            } ?>
                            </a>
                        </p>
                    </div>
                    <?php
                endif ?>
            </div>

            <div class="flex align-item-center">
                <?php
                if (IS_ADMIN) : ?>
                    <div class="slide-btn get-panel-btn mediumopacity" panel="repos/groups/list" title="Manage repos groups">
                        <img src="/assets/icons/folder.svg" />
                        <span>Groups</span>
                    </div>

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
            </div>
        </div>

        <?php
        if (IS_ADMIN or (!empty(USER_PERMISSIONS['repositories']['view']['groups']) or in_array('all', USER_PERMISSIONS['repositories']['view']))) { ?>
            <input id="repo-search-input" class="margin-bottom-10" type="text" placeholder="Search" onkeyup="searchRepo()" title="Search by repository name, distribution, section or release version" />

            <div id="hideAllReposGroups" class="flex justify-end column-gap-5 margin-bottom-10 margin-right-15 lowopacity pointer" state="visible">
                <img src="/assets/icons/view.svg" class="icon" title="Hide/Show all repositories groups" />
            </div>

            <div id="repos-list-container">
                <?php include_once(ROOT . '/views/includes/repos-list.inc.php'); ?>
            </div>
            <?php
        } else {
            echo '<p class="note">Nothing to show here!</p>';
        } ?>
    </div>

    <script>
        $(document).ready(function() {
            new EChart('doughnut', 'repo-storage-chart');
        });
    </script>
</section>