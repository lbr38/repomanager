<h6>UPDATE</h6>
<p class="note">Task will create a new snapshot of the repository.</p>

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

    <p>⸺<span class="label-red"><?= DATE_DMY ?></span></p>
</div>

<h6 class="required">ARCHITECTURE</h6>
<p class="note">Select the package architecture to sync.</p>

<select class="task-param" param-name="arch" multiple>
    <option value="">Select architecture...</option>
    <?php
    if ($repoController->getPackageType() == 'rpm') :
        foreach (RPM_ARCHS as $arch) {
            if (in_array($arch, $repoController->getArch())) {
                echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
            } else {
                echo '<option value="' . $arch . '">' . $arch . '</option>';
            }
        }
    endif;

    if ($repoController->getPackageType() == 'deb') :
        foreach (DEB_ARCHS as $arch) {
            if (in_array($arch, $repoController->getArch())) {
                echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
            } else {
                echo '<option value="' . $arch . '">' . $arch . '</option>';
            }
        }
    endif; ?>
</select>

<h6>POINT AN ENVIRONMENT</h6>
<p class="note">Select one or multiple environments to point to the new snapshot.</p>
<select id="update-repo-target-env-select-<?= $repoController->getSnapId() ?>" class="task-param" param-name="env" multiple>
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
if ($repoController->getType() == 'mirror') : ?>
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
    if ($repoController->getPackageType() == 'rpm') : ?>
        <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param type_rpm" value="true" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?>>
        <?php
    endif;

    if ($repoController->getPackageType() == 'deb') : ?>
        <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param type_deb" value="true" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?>>
        <?php
    endif ?>
    <span class="onoff-switch-slider"></span>
</label>

<?php
if ($repoController->getType() == 'mirror' or ($repoController->getType() == 'local' and $repoController->getPackageType() == 'deb')) : ?>
    <div class="flex align-item-center column-gap-5 toggle-btn pointer margin-top-20 mediumopacity" toggle="div#advanced-params-<?= $repoController->getSnapId() ?>">
        <h6 class="margin-top-0">ADVANCED PARAMETERS</h6>
        <img src="/assets/icons/next.svg" class="icon toggle-icon" />
    </div>

    <div id="advanced-params-<?= $repoController->getSnapId() ?>" class="hide">
        <?php
        if ($repoController->getType() == 'mirror') : ?>
            <h6>KEEP LATEST x VERSIONS OF PACKAGES</h6>
            <p class="note">Keep only the latest x versions of packages in the repository. Older versions will be ignored.</p>
            <input type="number" class="task-param" param-name="advanced-params.packages.keep-latest" package-type="all" min="1" placeholder="e.g. 5" value="<?= $repoController->getAdvancedParams()['packages']['keep-latest'] ?? '' ?>" />

            <h6>ONLY INCLUDE PACKAGE(S)</h6>
            <p class="note">Specify packages names to include. All other packages will be ignored from sync.</p>
            <p class="note">You can use <code>.*</code> as a wildcard. e.g <code>nginx_1.24.*</code></p>
            <select class="task-param" param-name="advanced-params.packages.include" multiple>
                <?php
                if (!empty($repoController->getAdvancedParams()['packages']['include'])) {
                    foreach ($repoController->getAdvancedParams()['packages']['include'] as $package) {
                        echo '<option value="' . $package . '" selected>' . $package . '</option>';
                    }
                } ?>
            </select>

            <h6>EXCLUDE PACKAGE(S)</h6>
            <p class="note">Specify packages names to exclude from sync.</p>
            <p class="note">You can use <code>.*</code> as a wildcard. e.g <code>nginx_1.24.*</code></p>
            <select class="task-param" param-name="advanced-params.packages.exclude" multiple>
                <?php
                if (!empty($repoController->getAdvancedParams()['packages']['exclude'])) {
                    foreach ($repoController->getAdvancedParams()['packages']['exclude'] as $package) {
                        echo '<option value="' . $package . '" selected>' . $package . '</option>';
                    }
                } ?>
            </select>
            <?php
        endif;

        if ($repoController->getPackageType() == 'deb') : ?>
            <h6>METADATA CUSTOM FIELDS</h6>

            <h6>ORIGIN</h6>
            <p class="note">Optional. Configure the <code>Origin</code> value in <code>Release</code> metadata file for this repository.</p>
            <input type="text" class="task-param" param-name="advanced-params.metadata-custom-fields.origin" value="<?= $repoController->getAdvancedParams()['metadata-custom-fields']['origin'] ?? '' ?>" placeholder="e.g. repository > distribution > component" />

            <h6>LABEL</h6>
            <p class="note">Optional. Configure the <code>Label</code> value in <code>Release</code> metadata file for this repository.</p>
            <input type="text" class="task-param" param-name="advanced-params.metadata-custom-fields.label" value="<?= $repoController->getAdvancedParams()['metadata-custom-fields']['label'] ?? '' ?>" placeholder="e.g. deb packages repository" />

            <h6>DESCRIPTION</h6>
            <p class="note">Optional. Configure the <code>Description</code> value in <code>Release</code> metadata file for this repository.</p>
            <input type="text" class="task-param" param-name="advanced-params.metadata-custom-fields.description" value="<?= $repoController->getAdvancedParams()['metadata-custom-fields']['description'] ?? '' ?>" placeholder="e.g. repository > distribution > component" />
            <?php
        endif ?>
    </div>
    <?php
endif ?>

<?php
// Define schedule form action (useful for the schedule form)
$scheduleForm['action'] = 'update'; ?>

<script>
$(document).ready(function(){
    myselect2.convert('#update-repo-target-env-select-<?= $repoController->getSnapId() ?>');
    myselect2.convert('select.task-param[param-name="arch"]', 'Select architecture(s)', true);
    myselect2.convert('select.task-param[param-name="advanced-params.packages.include"]', 'Specify package(s)', true);
    myselect2.convert('select.task-param[param-name="advanced-params.packages.exclude"]', 'Specify package(s)', true);
});
</script>