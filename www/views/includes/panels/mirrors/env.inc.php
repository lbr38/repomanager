<?php ob_start(); ?>

<form id="task-form" autocomplete="off">
    <?php
    foreach ($snapshots as $selected) : ?>
        <div class="task-form-params form-block form-block-accent-<?= $selected['mirror']['Package_type'] == 'deb' ? 'red' : 'blue' ?>" domain="mirror" action="env" mirror-id="<?= $selected['mirror']['Id'] ?>" mirror-snap-id="<?= $selected['snapshot']['Id'] ?>">
            <div class="flex align-item-center">
                <p class="label-white"><?= htmlspecialchars(\Controllers\Mirror\Mirror::getLabel($selected['mirror']['Name'], $selected['mirror']['Releasever'])) ?></p>
                <p>⸺<span class="label-black"><?= $selected['label'] ?></span></p>
            </div>

            <h6 class="required">ENVIRONMENT</h6>
            <p class="note">The environment URL is switched to this snapshot without interruption for clients.</p>
            <select class="task-param" param-name="env" multiple>
                <?php
                foreach ($envs as $env) {
                    // Already pointing to this snapshot
                    if (in_array($env, $selected['currentEnvs'])) {
                        continue;
                    }

                    echo '<option value="' . htmlspecialchars($env) . '">' . htmlspecialchars($env) . (in_array($env, $protectedEnvs) ? ' (protected)' : '') . '</option>';
                } ?>
            </select>
        </div>
        <?php
    endforeach;

    $scheduleForm['action'] = 'env';
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
$slidePanelName = 'mirrors/env';
$slidePanelTitle = 'POINT AN ENVIRONMENT';

include(ROOT . '/views/includes/slide-panel.inc.php');
