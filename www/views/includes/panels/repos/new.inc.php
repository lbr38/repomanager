<?php ob_start(); ?>
       
<form class="task-form" autocomplete="off">
    <div class="task-form-params" repo-id="none" action="create">
        <table class="task-table">
            <tr>
                <td>Package type</td>
                <td>
                    <?php
                    /**
                     *  Case where the server manages several different types of repo
                     */
                    if (RPM_REPO == 'true' and DEB_REPO == 'true') : ?>
                        <div class="switch-field">
                            <input type="radio" id="package-type-rpm" class="task-param" param-name="package-type" name="package-type" value="rpm" checked />
                            <label for="package-type-rpm">rpm</label>
                            <input type="radio" id="package-type-deb" class="task-param" param-name="package-type" name="package-type" value="deb" />
                            <label for="package-type-deb">deb</label>
                        </div>
                        <?php
                    elseif (RPM_REPO == 'true') : ?>
                        <div class="single-switch-field">
                            <input type="radio" id="package-type-rpm" class="task-param" param-name="package-type" name="package-type" value="rpm" checked />
                            <label for="package-type-rpm">rpm</label>
                        </div>
                        <?php
                    elseif (DEB_REPO == 'true') : ?>
                        <div class="single-switch-field">
                            <input type="radio" id="package-type-deb" class="task-param" param-name="package-type" name="package-type" value="deb" checked />
                            <label for="package-type-deb">deb</label>
                        </div>
                        <?php
                    endif ?>
                </td>
            </tr>
            <tr>
                <td>Repo type</td>
                <td>
                    <div class="switch-field">
                        <input type="radio" id="repo-type_mirror" class="task-param" param-name="repo-type" name="repo-type" value="mirror" package-type="all" checked />
                        <label for="repo-type_mirror">Mirror</label>
                        <input type="radio" id="repo-type_local" class="task-param" param-name="repo-type" name="repo-type" value="local" package-type="all" />
                        <label for="repo-type_local">Local</label>
                    </div>
                </td>
            </tr>
            <tr field-type="mirror rpm deb">
                <td>Source repo</td>
                <td>
                    <?php
                    if (RPM_REPO == 'true') : ?>
                        <select class="task-param" param-name="source" field-type="mirror rpm" package-type="rpm">
                            <option value="">Select a source repo...</option>
                            <?php
                            if (!empty($newRepoRpmSourcesList)) {
                                foreach ($newRepoRpmSourcesList as $source) {
                                    echo '<option value="' . $source['Name'] . '">' . $source['Name'] . '</option>';
                                }
                            } ?>
                        </select>
                        <?php
                    endif;

                    if (DEB_REPO == 'true') : ?>
                        <select class="task-param" param-name="source" field-type="mirror deb" package-type="deb">
                            <option value="">Select a source repo...</option>
                            <?php
                            if (!empty($newRepoDebSourcesList)) {
                                foreach ($newRepoDebSourcesList as $source) {
                                    echo '<option value="' . $source['Name'] . '">' . $source['Name'] . '</option>';
                                }
                            } ?>
                        </select>
                        <?php
                    endif ?>
                </td>
            </tr>
            <tr>
                <td field-type="mirror rpm deb">
                    <span>Custom repo name</span>
                    <span class="lowopacity-cst">(optional)</span>
                </td>
                <td field-type="local rpm deb">Repo name</td>
                <td>
                    <input type="text" class="task-param" param-name="alias" package-type="all" />
                </td>
            </tr>
            <tr field-type="mirror local rpm">
                <td>Release version</td>
                <td>
                    <select class="task-param" param-name="releasever" package-type="rpm" multiple>
                        <option value="7" <?php echo (RELEASEVER == 7) ? 'selected' : '' ?>>7 (Redhat 7 and derivatives)</option>
                        <option value="8" <?php echo (RELEASEVER == 8) ? 'selected' : '' ?>>8 (Redhat 8 and derivatives)</option>
                        <option value="9" <?php echo (RELEASEVER == 9) ? 'selected' : '' ?>>9 (Redhat 9 and derivatives)</option>
                    </select>
                </td>
            </tr>
            <tr field-type="mirror local deb">
                <td>Distribution</td>
                <td>
                    <select class="task-param" param-name="dist" package-type="deb" multiple>
                        <optgroup label="Debian">
                            <?php
                            foreach (DEBIAN_DISTRIBUTIONS as $dist => $alias) {
                                echo '<option value="' . $dist . '">' . $dist . ' (' . $alias . ')</option>';
                            } ?>
                        </optgroup>
                        <optgroup label="Ubuntu">
                            <?php
                            foreach (UBUNTU_DISTRIBUTIONS as $dist => $alias) {
                                echo '<option value="' . $dist . '">' . $dist . ' (' . $alias . ')</option>';
                            } ?>
                        </optgroup>
                    </select>
                </td>
            </tr>

            <tr field-type="mirror local deb">
                <td>Section</td>
                <td>
                    <select class="task-param" param-name="section" package-type="deb" multiple>
                        <option value="main">main</option>
                        <option value="contrib">contrib</option>
                        <option value="non-free">non-free</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Point an environment</td>
                <td>
                    <select id="new-repo-target-env-select" class="task-param" param-name="env" package-type="all">
                        <option value=""></option>
                        <?php
                        foreach (ENVS as $env) {
                            if ($env == DEFAULT_ENV) {
                                echo '<option value="' . $env . '" selected>' . $env . '</option>';
                            } else {
                                echo '<option value="' . $env . '">' . $env . '</option>';
                            }
                        } ?>
                    </select>
                </td>
            </tr>

            <tr id="new-repo-target-description-tr">
                <td>
                    <span>Description</span>
                    <span class="lowopacity-cst">(optional)</span>
                </td>
                <td><input type="text" class="task-param" param-name="description" package-type="all" /></td>
            </tr>

            <?php
            /**
             *  Possibility to add to a group, if there is at least one group
             */
            if (!empty($newRepoFormGroupList)) : ?>
                <tr>
                    <td>
                        <span>Add to group</span>
                        <span class="lowopacity-cst">(optional)</span>
                    </td>
                    <td>
                        <select class="task-param" param-name="group" package-type="all" >
                            <option value="">Select group...</option>
                            <?php
                            foreach ($newRepoFormGroupList as $group) {
                                echo '<option value="' . $group['Name'] . '">' . $group['Name'] . '</option>';
                            } ?>
                        </select>
                    </td>
                </tr>
                <?php
            endif ?>

            <tr field-type="mirror rpm deb">
                <td colspan="100%"><b>GPG parameters</b></td>
            </tr>

            <tr field-type="mirror rpm deb">
                <td>Check GPG signatures</td>
                <td>
                    <label class="onoff-switch-label">
                        <input type="checkbox" class="onoff-switch-input task-param" value="true" param-name="gpg-check" package-type="all" checked />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>

            <tr field-type="mirror rpm deb">
                <td>Sign with GPG</td>
                <td>
                    <label class="onoff-switch-label" field-type="mirror rpm">
                        <input type="checkbox" class="onoff-switch-input task-param type_rpm" value="true" param-name="gpg-sign" package-type="rpm" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                    <label class="onoff-switch-label" field-type="mirror deb">
                        <input type="checkbox" class="onoff-switch-input task-param type_deb" value="true" param-name="gpg-sign" package-type="deb" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>

            <tr field-type="mirror rpm deb">
                <td colspan="100%"><b>Advanced parameters</b></td>
            </tr>

            <tr field-type="mirror local rpm deb">
                <td>Architecture</td>
                <td field-type="mirror local rpm">
                    <select class="task-param" param-name="arch" package-type="rpm" multiple>
                        <?php
                        foreach (RPM_ARCHS as $arch) {
                            if (in_array($arch, RPM_DEFAULT_ARCH)) {
                                echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                            } else {
                                echo '<option value="' . $arch . '">' . $arch . '</option>';
                            }
                        } ?>
                    </select>
                </td>
                <td field-type="mirror local deb">
                    <select class="task-param" param-name="arch" package-type="deb" multiple>
                        <?php
                        foreach (DEB_ARCHS as $arch) {
                            if (in_array($arch, DEB_DEFAULT_ARCH)) {
                                echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                            } else {
                                echo '<option value="' . $arch . '">' . $arch . '</option>';
                            }
                        } ?>
                    </select>
                </td>
            </tr>

            <tr field-type="mirror rpm deb">
                <td>
                    <span>Only include package(s)</span>
                    <span class="lowopacity-cst">(optional)</span>
                </td>

                <td field-type="mirror rpm deb">
                    <select class="task-param" param-name="package-include" package-type="all" multiple></select>
                </td>
            </tr>

            <tr field-type="mirror rpm deb">
                <td>
                    <span>Exclude package(s)</span>
                    <span class="lowopacity-cst">(optional)</span>
                </td>

                <td field-type="mirror rpm deb">
                    <select class="task-param" param-name="package-exclude" package-type="all" multiple></select>
                </td>
            </tr>
        </table>
    </div>

    <?php
    /**
     *  Define schedule form action and allowed type(s)
     */
    $scheduleForm['action'] = 'create';
    $scheduleForm['type'] = array('unique');

    /**
     *  Include schedule task template
     */
    include_once(ROOT . '/views/includes/forms/tasks/schedule.inc.php'); ?>
    
    <br>
    <button class="task-confirm-btn btn-large-red">Execute now</button>
</form>

<script>
$(document).ready(function(){
    selectToSelect2('select.task-param[param-name="schedule-day"]', 'Select day(s)...', true);
    selectToSelect2('select.task-param[param-name="schedule-reminder"]', 'Select reminder...', true);
    selectToSelect2('select.task-param[param-name="schedule-recipient"]', 'Select or add recipients...', true);
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/new';
$slidePanelTitle = 'NEW REPOSITORY';

include(ROOT . '/views/includes/slide-panel.inc.php');
