<h6>POINT ENVIRONMENT TO SNAPSHOT</h6>

<?php
if ($myrepo->getPackageType() == 'rpm') {
    echo '<span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
}
if ($myrepo->getPackageType() == 'deb') {
    echo '<span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
} ?>

<h6>TARGET ENVIRONMENT</h6>
<select class="task-param" param-name="env" required>
    <?php
    foreach (ENVS as $env) {
        /**
         *  Don't display the environment if it already exists
         */
        if ($myrepo->existsSnapIdEnv($myrepo->getSnapId(), $env)) {
            continue;
        }

        echo '<option value="' . $env . '">' . $env . '</option>';
    } ?>
</select>

<h6>DESCRIPTION</h6>
<p class="note">Optional</p>
<input type="text" class="task-param" param-name="description" />

<?php
/**
 *  Define schedule form action and allowed type(s)
 */
$scheduleForm['action'] = 'env';
$scheduleForm['type'] = array('unique'); ?>