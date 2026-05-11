<?php
use \Controllers\User\Permission\Repo as RepoPermission; ?>

<section class="section-right reloadable-container repo-side-workspace" container="repos/properties">
    <h3>WORKSPACE</h3>

    <div class="repo-kpi-grid">
        <div class="repo-kpi-card">
            <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
            <div>
                <p class="repo-kpi-value"><?= $totalRepos ?></p>
                <p class="mediumopacity-cst"><?= $totalRepos <= 1 ? 'Repository' : 'Repositories' ?></p>
            </div>
        </div>

        <div class="repo-kpi-card">
            <img src="/assets/icons/server.svg" class="icon-np icon-medium" />
            <div>
                <p class="repo-kpi-value"><?= $diskUsedSpacePercent ?>%</p>
                <p class="mediumopacity-cst">Storage used</p>
            </div>
        </div>
    </div>

    <div class="div-generic-blue repo-storage-card">
        <div class="flex justify-space-between align-item-center margin-bottom-10">
            <h6 class="margin-top-0">STORAGE</h6>
            <p class="mediumopacity-cst"><?= $diskUsedSpaceHuman ?> / <?= $diskFreeSpaceHuman ?> free</p>
        </div>
        <div class="repo-storage-meter" title="<?= $diskUsedSpacePercent ?>% used storage">
            <span style="width: <?= $diskUsedSpacePercent ?>%"></span>
        </div>
    </div>

    <div class="div-generic-blue repo-task-card">
        <div class="flex justify-space-between align-item-center margin-bottom-15">
            <h6 class="margin-top-0">SCHEDULE</h6>
            <a href="/run" class="mediumopacity-cst">All tasks</a>
        </div>

        <?php
        if (!empty($lastScheduledTask) and !empty($lastScheduledTask['Date']) and !empty($lastScheduledTask['Time'])) :
            if ($lastScheduledTask['Status'] == 'error' or $lastScheduledTask['Status'] == 'stopped') {
                $icon = 'warning-red';
                $label = 'Failed';
            } else {
                $icon = 'check';
                $label = 'Successful';
            } ?>
            <a href="/run/<?= $lastScheduledTask['Id'] ?>" class="repo-timeline-item">
                <img src="/assets/icons/<?= $icon ?>.svg" class="icon-np icon-medium" />
                <div>
                    <p><?= $label ?></p>
                    <p class="mediumopacity-cst"><?= DateTime::createFromFormat('Y-m-d', $lastScheduledTask['Date'])->format('d-m-Y') . ' ' . $lastScheduledTask['Time'] ?></p>
                </div>
            </a>
            <?php
        endif;

        if (!empty($nextScheduledTasks)) :
            foreach (array_slice($nextScheduledTasks, 0, 3) as $scheduledTask) : ?>
                <a href="/run" class="repo-timeline-item">
                    <img src="/assets/icons/time.svg" class="icon-np icon-medium mediumopacity-cst" />
                    <div>
                        <p>
                            <?php
                            if ($scheduledTask['left']['days'] > 0) {
                                echo $scheduledTask['left']['days'] . ' days left';
                            } else {
                                echo $scheduledTask['left']['time'] . ' left';
                            } ?>
                        </p>
                        <p class="mediumopacity-cst">
                            <?php
                            if (!empty($scheduledTask['date'])) {
                                echo DateTime::createFromFormat('Y-m-d', $scheduledTask['date'])->format('d-m-Y') . ' ';
                            }

                            if (!empty($scheduledTask['time'])) {
                                echo $scheduledTask['time'];
                            } ?>
                        </p>
                    </div>
                </a>
                <?php
            endforeach;
        endif;

        if (empty($lastScheduledTask) and empty($nextScheduledTasks)) : ?>
            <p class="note">No scheduled task</p>
            <?php
        endif ?>
    </div>

    <div class="div-generic-blue repo-quick-actions">
        <h6 class="margin-top-0 margin-bottom-15">QUICK ACCESS</h6>

        <?php
        if (RepoPermission::allowedAction('create')) : ?>
            <div class="repo-action-row get-panel-btn" panel="repos/new">
                <img src="/assets/icons/plus.svg" class="icon-np icon-medium" />
                <span>Create repository</span>
            </div>
            <?php
        endif;

        if (RepoPermission::allowedAction('edit-source')) : ?>
            <div class="repo-action-row get-panel-btn" panel="repos/sources/list">
                <img src="/assets/icons/internet.svg" class="icon-np icon-medium" />
                <span>Source repositories</span>
            </div>
            <?php
        endif;

        if (RepoPermission::allowedAction('edit-groups')) : ?>
            <div class="repo-action-row get-panel-btn" panel="repos/groups/list">
                <img src="/assets/icons/folder.svg" class="icon-np icon-medium" />
                <span>Repository groups</span>
            </div>
            <?php
        endif ?>
    </div>
</section>
