<h6>UPDATE</h6>
<p class="note">Task will create a new snapshot of the repository.</p>

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

    <p>⸺<span class="label-red"><?= DATE_DMY ?></span></p>
</div>

<h6 class="required">ARCHITECTURE</h6>
<p class="note">Select the package architecture to sync.</p>

<select class="task-param" param-name="arch" multiple>
    <option value="">Select architecture...</option>
    <?php
    if ($myrepo->getPackageType() == 'rpm') :
        foreach (RPM_ARCHS as $arch) {
            if (in_array($arch, $myrepo->getArch())) {
                echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
            } else {
                echo '<option value="' . $arch . '">' . $arch . '</option>';
            }
        }
    endif;

    if ($myrepo->getPackageType() == 'deb') :
        foreach (DEB_ARCHS as $arch) {
            if (in_array($arch, $myrepo->getArch())) {
                echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
            } else {
                echo '<option value="' . $arch . '">' . $arch . '</option>';
            }
        }
    endif; ?>
</select>

<h6>POINT AN ENVIRONMENT</h6>
<p class="note">Select one or multiple environments to point to the new snapshot.</p>
<select id="update-repo-target-env-select-<?= $myrepo->getSnapId() ?>" class="task-param" param-name="env" multiple>
    <option value=""></option>
    <?php
    foreach (ENVS as $env) {
        if ($env['Name'] == DEFAULT_ENV) {
            echo '<option value="' . $env['Name'] . '" selected>' . $env['Name'] . '</option>';
        } else {
            echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
        }
    } ?>
</select>

<h6>GPG PARAMETERS</h6>

<?php
if ($myrepo->getType() == 'mirror') : ?>
    <h6>CHECK GPG SIGNATURES</h6>
    <p class="note">Check GPG signature of repository / packages.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" param-name="gpg-check" class="onoff-switch-input task-param" value="true" checked />
        <span class="onoff-switch-slider"></span>
    </label>
    <?php
endif ?>

<h6>SIGN WITH GPG</h6>
<p class="note">Sign repository / packages with GPG.</p>
<label class="onoff-switch-label">
    <?php
    if ($myrepo->getPackageType() == 'rpm') : ?>
        <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param type_rpm" value="true" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?>>
        <?php
    endif;

    if ($myrepo->getPackageType() == 'deb') : ?>
        <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param type_deb" value="true" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?>>
        <?php
    endif ?>
    <span class="onoff-switch-slider"></span>
</label>

<?php
if ($myrepo->getType() == 'mirror') : ?>
    <h6>ADDITIONAL PARAMETERS</h6>

    <h6>ONLY INCLUDE PACKAGE(S)</h6>
    <p class="note">Specify packages names to include. All other packages will be ignored from sync.</p>
    <p class="note">You can use <code>.*</code> as a wildcard. e.g <code>nginx_1.24.*</code></p>
    <select class="task-param" param-name="package-include" multiple>
        <?php
        if (!empty($myrepo->getPackagesToInclude())) {
            foreach ($myrepo->getPackagesToInclude() as $package) {
                echo '<option value="' . $package . '" selected>' . $package . '</option>';
            }
        } ?>
    </select>

    <h6>EXCLUDE PACKAGE(S)</h6>
    <p class="note">Specify packages names to exclude from sync.</p>
    <p class="note">You can use <code>.*</code> as a wildcard. e.g <code>nginx_1.24.*</code></p>
    <select class="task-param" param-name="package-exclude" multiple>
        <?php
        if (!empty($myrepo->getPackagesToExclude())) {
            foreach ($myrepo->getPackagesToExclude() as $package) {
                echo '<option value="' . $package . '" selected>' . $package . '</option>';
            }
        } ?>
    </select>
    <?php
endif;

/**
 *  Define schedule form action and allowed type(s)
 */
$scheduleForm['action'] = 'update';
$scheduleForm['type'] = array('unique', 'recurring'); ?>

<script>
$(document).ready(function(){
    /**
     *  Convert select to select2
     */
    myselect2.convert('#update-repo-target-env-select-<?= $myrepo->getSnapId() ?>');
    myselect2.convert('select.task-param[param-name="arch"]', 'Select architecture(s)', true);
    myselect2.convert('select.task-param[param-name="package-include"]', 'Specify package(s)', true);
    myselect2.convert('select.task-param[param-name="package-exclude"]', 'Specify package(s)', true);
});
</script>