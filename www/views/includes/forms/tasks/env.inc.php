<h6>POINT AN ENVIRONMENT</h6>
<p class="note">The repository snapshot to point the environment(s) to.</p>

<div class="flex align-item-center">
    <p class="label-white">
        <?php
        if ($repoController->getPackageType() == 'rpm') {
            echo $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
        }
        if ($repoController->getPackageType() == 'deb') {
            echo $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
        } ?>
    </p>

    <p>⸺<span class="label-black"><?= $repoController->getDateFormatted() ?></span></p>
</div>

<h6 class="required">ENVIRONMENT</h6>
<p class="note">Select one or multiple environments to point to the repository snapshot.</p>
<select id="point-env-target-env-select-<?= $repoController->getSnapId() ?>" class="task-param" param-name="env" multiple required>
    <?php
    $selected = false;

    foreach (ENVS as $env) {
        // Don't display the environment if it already exists
        if ($repoController->existsSnapIdEnv($repoController->getSnapId(), $env['Name'])) {
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

<?php
// Define schedule form action (useful for the schedule form)
$scheduleForm['action'] = 'env'; ?>

<script>
$(document).ready(function() {
    myselect2.convert('#point-env-target-env-select-<?= $repoController->getSnapId() ?>');
});
</script>
