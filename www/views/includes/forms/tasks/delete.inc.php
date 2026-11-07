<?php
if ($repoController->getPackageType() == 'rpm') {
    echo '<span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getReleasever() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span>';
}
if ($repoController->getPackageType() == 'deb') {
    echo '<p><span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span></p>';
}

// A snapshot used by a protected environment cannot be deleted, the deletion would be refused
// Other environments pointing to the snapshot are removed with it
$protectedEnvs = (new \Controllers\Repo\Snapshot\Snapshot())->getProtectedEnvs((int) $snapId);
$snapshotEnvs = (new \Controllers\Repo\Environment())->getBySnapId((int) $snapId);

if (!empty($protectedEnvs)) : ?>
    <div class="note-warning">
        <img src="/assets/icons/warning.svg" />
        <p>This snapshot is used by <?= \Controllers\Environment::nameList($protectedEnvs, 'protected') ?> and cannot be deleted.</p>
    </div>
    <?php
elseif (!empty($snapshotEnvs)) : ?>
    <div class="note-warning">
        <img src="/assets/icons/warning.svg" />
        <p>This snapshot is used by <?= \Controllers\Environment::nameList($snapshotEnvs) ?>: <?= count($snapshotEnvs) > 1 ? 'they will be removed along with the snapshot, their URLs' : 'it will be removed along with the snapshot, its URL' ?> will no longer be available to clients.</p>
    </div>
    <?php
endif;

if ($scheduledTasksCount > 0) : ?>
    <div class="note-warning">
        <img src="/assets/icons/warning.svg" />
        <p>There <?= $scheduledTasksCount > 1 ? 'are' : 'is' ?> <b><?= $scheduledTasksCount ?></b> scheduled <?= $scheduledTasksCount > 1 ? 'tasks' : 'task' ?> associated with this snapshot. Deleting this snapshot will also delete those tasks (a task targeting several snapshots only loses this one).</p>
    </div>
    <?php
endif;

// Define schedule form action (useful for the schedule form)
$scheduleForm['action'] = 'delete'; ?>
