<h6>POINT AN ENVIRONMENT</h6>
<p class="note">The repository snapshot to point the environment to.</p>

<?php
if ($myrepo->getPackageType() == 'rpm') {
    echo '<span class="label-white">' . $myrepo->getName() . '</span>';
}
if ($myrepo->getPackageType() == 'deb') {
    echo '<span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>';
} ?>
⸺<span class="label-black"><?= $myrepo->getDateFormatted() ?></span><span id="point-env-show-target-env-<?= $myrepo->getSnapId() ?>"></span>

<h6 class="required">ENVIRONMENT</h6>
<select id="point-env-target-env-select-<?= $myrepo->getSnapId() ?>" class="task-param" param-name="env" required>
    <?php
    foreach (ENVS as $env) {
        /**
         *  Don't display the environment if it already exists
         */
        if ($myrepo->existsSnapIdEnv($myrepo->getSnapId(), $env['Name'])) {
            continue;
        }

        echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
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
    /**
     *  Update repo->date<-env schema if an env is selected
     */
    var selectId = '#point-env-target-env-select-<?= $myrepo->getSnapId() ?>';
    var envSelector = '#point-env-show-target-env-<?= $myrepo->getSnapId() ?>';
    var selectedEnv = $(selectId).val();

    // If no environment is selected, don't display anything
    if (selectedEnv == "") {
        $(envSelector).html('');
    
    // Else display the environment that points to the snapshot
    } else {
        printEnv(selectedEnv, envSelector);
    }

    // Update the environment when another environment is selected
    $(document).on('change', selectId, function() {
        var selectedEnv = $(this).val();
        if (selectedEnv == "") {
            $(envSelector).html('');
        } else {
            printEnv(selectedEnv, envSelector);
        }
    }).trigger('change');
});
</script>
