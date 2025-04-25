<h6>POINT AN ENVIRONMENT</h6>
<p class="note">The repository snapshot to point the environment(s) to.</p>

<div class="flex align-item-center">
    <p class="label-white">
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo $myrepo->getName();
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection();
        } ?>
    </p>

    <p>⸺<span class="label-black"><?= $myrepo->getDateFormatted() ?></span></p>
</div>

<h6 class="required">ENVIRONMENT</h6>
<p class="note">Select one or multiple environments to point to the repository snapshot.</p>
<select id="point-env-target-env-select-<?= $myrepo->getSnapId() ?>" class="task-param" param-name="env" multiple required>
    <?php
    $selected = false;

    foreach (ENVS as $env) {
        // Don't display the environment if it already exists
        if ($myrepo->existsSnapIdEnv($myrepo->getSnapId(), $env['Name'])) {
            continue;
        }

        // Pre-select one environment by default, the first of the list, then avoid the next ones to be selected
        if (!$selected) {
            $selected = true;
            echo '<option value="' . $env['Name'] . '" selected>' . $env['Name'] . '</option>';
        } else {
            echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
        }
    } ?>
</select>

<h6>DESCRIPTION</h6>
<input type="text" class="task-param" param-name="description" />

<?php
/**
 *  Define schedule form action and allowed type(s)
 */
$scheduleForm['action'] = 'env';
$scheduleForm['type'] = array('unique'); ?>

<script>
$(document).ready(function() {
    selectToSelect2('#point-env-target-env-select-<?= $myrepo->getSnapId() ?>');
});
</script>
