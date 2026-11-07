<?php ob_start(); ?>

<form id="task-form" autocomplete="off">
    <?php
    foreach ($snapshots as $selected) : ?>
        <div class="task-form-params form-block form-block-accent-<?= \Controllers\Mirror\Mirror::getAccent($selected['mirror']['Package_type']) ?>" domain="mirror" action="env" mirror-id="<?= $selected['mirror']['Id'] ?>" mirror-snap-id="<?= $selected['snapshot']['Id'] ?>">
            <div class="flex align-item-center">
                <p class="label-white"><?= htmlspecialchars(\Controllers\Mirror\Mirror::getFullLabel($selected['mirror'])) ?></p>
                <p>⸺<span class="label-black"><?= $selected['label'] ?></span></p>
            </div>

            <h6 class="required">ENVIRONMENT</h6>
            <p class="note">The environment URL is switched to this snapshot without interruption for clients.</p>
            <select class="task-param" param-name="env" multiple>
                <?php
                $preselected = false;

                foreach ($envs as $env) {
                    // Already pointing to this snapshot
                    if (in_array($env, $selected['currentEnvs'])) {
                        continue;
                    }

                    // Pre-select one environment by default, the first of the list, as for repositories
                    echo '<option value="' . htmlspecialchars($env) . '"' . (!$preselected ? ' selected' : '') . '>' . htmlspecialchars($env) . '</option>';
                    $preselected = true;
                } ?>
            </select>
            <?= \Controllers\Environment::protectedNote() ?>
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
