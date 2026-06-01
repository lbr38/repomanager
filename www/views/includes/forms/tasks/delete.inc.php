<h6>DELETE</h6>
<p class="note">The repository snapshot to delete.</p>
<?php
if ($repoController->getPackageType() == 'rpm') {
    echo '<span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getReleasever() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span>';
}
if ($repoController->getPackageType() == 'deb') {
    echo '<p><span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>⸺<span class="label-black">' . $repoController->getDateFormatted() . '</span></p>';
}

if ($scheduledTasksCount > 0) : ?>
    <div class="flex align-item-center column-gap-5 margin-top-15">
        <img src="/assets/icons/warning.svg" class="icon-np" />
        <p class="note yellowtext">There <?= $scheduledTasksCount > 1 ? 'are' : 'is' ?> <b><?= $scheduledTasksCount ?></b> scheduled <?= $scheduledTasksCount > 1 ? 'tasks' : 'task' ?> associated with this snapshot. Deleting this snapshot will also delete those tasks.</p>
    </div>
    <?php
endif;

// Define schedule form action (useful for the schedule form)
$scheduleForm['action'] = 'delete'; ?>
