<?php ob_start(); ?>

<form id="task-form" autocomplete="off">
    <?php
    foreach ($snapshots as $selected) : ?>
        <div class="task-form-params form-block form-block-accent-<?= \Controllers\Mirror\Mirror::getAccent($selected['mirror']['Package_type']) ?>" domain="mirror" action="delete" mirror-id="<?= $selected['mirror']['Id'] ?>" mirror-snap-id="<?= $selected['snapshot']['Id'] ?>">
            <div class="flex align-item-center">
                <p class="label-white"><?= htmlspecialchars(\Controllers\Mirror\Mirror::getFullLabel($selected['mirror'])) ?></p>
                <p>⸺<span class="label-black"><?= $selected['label'] ?></span></p>
            </div>

            <?php
            // A snapshot used by a protected environment cannot be deleted, the task would be refused
            if (!empty($selected['protectedEnvs'])) : ?>
                <div class="note-warning">
                    <img src="/assets/icons/warning.svg" />
                    <p>This snapshot is used by <?= \Controllers\Environment::nameList($selected['protectedEnvs'], 'protected') ?> and cannot be deleted.</p>
                </div>
                <?php
            // Other environments are removed with the snapshot
            elseif (!empty($selected['envs'])) : ?>
                <div class="note-warning">
                    <img src="/assets/icons/warning.svg" />
                    <p>This snapshot is used by <?= \Controllers\Environment::nameList($selected['envs']) ?>: <?= count($selected['envs']) > 1 ? 'they will be removed along with the snapshot, their URLs' : 'it will be removed along with the snapshot, its URL' ?> will no longer be available to clients.</p>
                </div>
                <?php
            endif;

            if ($selected['lastOfMirror']) : ?>
                <div class="note-warning">
                    <img src="/assets/icons/warning.svg" />
                    <p>No snapshot of this mirror will be left: the mirror will be deleted, with its environments and scheduled tasks.</p>
                </div>
                <?php
            endif ?>
        </div>
        <?php
    endforeach;

    $scheduleForm['action'] = 'delete';
    include_once(ROOT . '/views/includes/forms/tasks/schedule.inc.php'); ?>

    <br>
    <button class="task-confirm-btn btn-large-red">Execute now</button>
</form>

<script>
$(document).ready(function(){
    myselect2.convert('select.task-param[param-name="schedule-day"]', 'Select day(s)', true);
    myselect2.convert('select.task-param[param-name="schedule-reminder"]', 'Select reminder(s)', true);
    myselect2.convert('select.task-param[param-name="schedule-recipient"]', 'Select or add recipient(s)', true);
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'mirrors/delete';
$slidePanelTitle = 'DELETE MIRROR SNAPSHOT';

include(ROOT . '/views/includes/slide-panel.inc.php');
