<?php ob_start(); ?>

<form id="task-form" autocomplete="off">
    <div class="task-form-params form-block form-block-accent-<?= $mirror['Package_type'] == 'deb' ? 'red' : 'blue' ?>" domain="mirror" action="update" mirror-id="<?= $mirror['Id'] ?>">
        <div class="flex align-item-center">
            <p class="label-white"><?= htmlspecialchars(\Controllers\Mirror\Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) ?></p>
            <p>⸺<span class="label-red"><?= DATE_DMY ?></span></p>
        </div>

        <p class="note margin-top-10">A new snapshot is created from the source repository <b><?= htmlspecialchars($mirror['Source']) ?></b>. Unchanged files are hard linked from the latest snapshot.</p>

        <h6>POINT AN ENVIRONMENT</h6>
        <p class="note">Optional. Environments are switched to the new snapshot once it is complete.</p>
        <select class="task-param" param-name="env" multiple>
            <?php
            foreach ($envs as $env) {
                echo '<option value="' . htmlspecialchars($env) . '">' . htmlspecialchars($env) . '</option>';
            } ?>
        </select>
    </div>

    <?php
    $scheduleForm['action'] = 'update';
    include_once(ROOT . '/views/includes/forms/tasks/schedule.inc.php'); ?>

    <br>
    <button class="task-confirm-btn btn-large-red">Execute now</button>
</form>

<script>
$(document).ready(function(){
    myselect2.convert('select.task-param[param-name="env"]', 'Select environment(s)');
    myselect2.convert('select.task-param[param-name="schedule-day"]', 'Select day(s)', true);
    myselect2.convert('select.task-param[param-name="schedule-reminder"]', 'Select reminder(s)', true);
    myselect2.convert('select.task-param[param-name="schedule-recipient"]', 'Select or add recipient(s)', true);
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'mirrors/update';
$slidePanelTitle = 'SYNC MIRROR';

include(ROOT . '/views/includes/slide-panel.inc.php');
