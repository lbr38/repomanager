<?php ob_start(); ?>
       
<form id="task-form" autocomplete="off">
    <div class="task-form-params" repo-id="none" action="create">
        <h6 class="required">PACKAGE TYPE</h6>
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

        <h6 class="required">REPOSITORY TYPE</h6>
        <div class="switch-field">
            <input type="radio" id="repo-type_mirror" class="task-param" param-name="repo-type" name="repo-type" value="mirror" package-type="all" checked />
            <label for="repo-type_mirror">Mirror</label>
            <input type="radio" id="repo-type_local" class="task-param" param-name="repo-type" name="repo-type" value="local" package-type="all" />
            <label for="repo-type_local">Local</label>
        </div>

        <div field-type="mirror rpm deb">
            <h6 class="required">SOURCE REPOSITORY</h6>
            <p class="note">The repository to mirror from. Want more? <span class="note pointer lowopacity get-panel-btn" panel="repos/sources/list">Add or import a source repository</span>.</p>
            <?php
            if (RPM_REPO == 'true') :
                if (empty($newRepoRpmSourcesList)) {
                    echo '<div class="flex align-item-center column-gap-5 margin-top-10" field-type="mirror rpm"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /><p class="note">No rpm source repositories available. Please add a source repository first.</p></div>';
                }

                if (!empty($newRepoRpmSourcesList)) : ?>
                    <select class="task-param" param-name="source" field-type="mirror rpm" package-type="rpm">
                        <option value="">Select a source repository</option>
                        <?php
                        foreach ($newRepoRpmSourcesList as $source) {
                            $definition = json_decode($source['Definition'], true);
                            $name = $definition['name'];
                            echo '<option value="' . $name . '">' . $name . '</option>';
                        } ?>
                    </select>
                    <?php
                endif;
            endif;

            if (DEB_REPO == 'true') :
                if (empty($newRepoDebSourcesList)) {
                    echo '<div class="flex align-item-center column-gap-5 margin-top-10" field-type="mirror deb"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /><p class="note">No deb source repositories available. Please add a source repository first.</p></div>';
                }

                if (!empty($newRepoDebSourcesList)) : ?>
                    <select class="task-param" param-name="source" field-type="mirror deb" package-type="deb">
                        <option value="">Select a source repository</option>
                        <?php
                        foreach ($newRepoDebSourcesList as $source) {
                            $definition = json_decode($source['Definition'], true);
                            $name = $definition['name'];
                            echo '<option value="' . $name . '">' . $name . '</option>';
                        } ?>
                    </select>
                    <?php
                endif;
            endif ?>
        </div>

        <div>
            <div field-type="mirror rpm deb">
                <h6>REPOSITORY NAME</h6>
                <p class="note">Optional. Default will be the source repository name.</p>
            </div>

            <div field-type="local rpm deb">
                <h6 class="required">REPOSITORY NAME</h6>
                <p class="note">The name of the local repository.</p>
            </div>
            <input type="text" class="task-param" param-name="alias" package-type="all" />
        </div>

        <div field-type="mirror local rpm">
            <h6 class="required">RELEASE VERSION</h6>
            <p class="note">Select or enter the release version.</p>
            <select class="task-param" param-name="releasever" package-type="rpm" multiple></select>
        </div>

        <div field-type="mirror local deb">
            <h6 class="required">DISTRIBUTION</h6>
            <p class="note">Select or enter the distribution.</p>
            <select class="task-param" param-name="dist" package-type="deb" multiple></select>

            <h6 class="required">COMPONENT</h6>
            <p class="note">Select or enter the component.</p>
            <select class="task-param" param-name="section" package-type="deb" multiple></select>
        </div>

        <h6>POINT AN ENVIRONMENT</h6>
        <p class="note">Point an environment to the newly created repository.</p>
        <select id="new-repo-target-env-select" class="task-param" param-name="env" package-type="all">
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

        <div id="new-repo-target-description-tr">
            <h6>DESCRIPTION</h6>
            <input type="text" class="task-param" param-name="description" package-type="all" />
        </div>

        <?php
        /**
         *  Possibility to add to a group, if there is at least one group
         */
        if (!empty($newRepoFormGroupList)) : ?>
            <h6>ADD TO GROUP</h6>
            <select class="task-param" param-name="group" package-type="all" >
                <option value="">Select a group</option>
                <?php
                foreach ($newRepoFormGroupList as $group) {
                    echo '<option value="' . $group['Name'] . '">' . $group['Name'] . '</option>';
                } ?>
            </select>
            <?php
        endif ?>

        <div field-type="mirror rpm deb">
            <h6>GPG PARAMETERS</h6>

            <h6>CHECK GPG SIGNATURES</h6>
            <p class="note">Check GPG signature of repository / packages.</p>
            <label class="onoff-switch-label">
                <input type="checkbox" class="onoff-switch-input task-param" value="true" param-name="gpg-check" package-type="all" checked />
                <span class="onoff-switch-slider"></span>
            </label>

            <h6>SIGN WITH GPG</h6>
            <p class="note">Sign repository / packages with GPG.</p>
            <label class="onoff-switch-label" field-type="mirror rpm">
                <input type="checkbox" class="onoff-switch-input task-param type_rpm" value="true" param-name="gpg-sign" package-type="rpm" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?> />
                <span class="onoff-switch-slider"></span>
            </label>
            <label class="onoff-switch-label" field-type="mirror deb">
                <input type="checkbox" class="onoff-switch-input task-param type_deb" value="true" param-name="gpg-sign" package-type="deb" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?> />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>

        <div field-type="mirror local rpm deb">
            <h6>ADDITIONAL PARAMETERS</h6>

            <div field-type="mirror local rpm deb">
                <h6 class="required">ARCHITECTURE</h6>

                <div field-type="mirror local rpm">
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
                </div>

                <div field-type="mirror local deb">
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
                </div>
            </div>
        </div>

        <div field-type="mirror rpm deb">
            <h6>ONLY INCLUDE PACKAGE(S)</h6>
            <p class="note">Specify packages names to include. All other packages will be ignored from sync.</p>
            <p class="note">You can use <code>.*</code> as a wildcard. e.g <code>nginx_1.24.*</code></p>
            <select class="task-param" param-name="package-include" package-type="all" multiple></select>

            <h6>EXCLUDE PACKAGE(S)</h6>
            <p class="note">Specify packages names to exclude from sync.</p>
            <p class="note">You can use <code>.*</code> as a wildcard. e.g <code>nginx_1.24.*</code></p>
            <select class="task-param" param-name="package-exclude" package-type="all" multiple></select>
        </div>
    </div>

    <br>
    <hr>

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
    /**
     *  Convert select to select2
     */
    selectToSelect2('.task-param[param-name="releasever"]', 'Select release version', true);
    selectToSelect2('.task-param[param-name="dist"]', 'Select distribution', true);
    selectToSelect2('.task-param[param-name="section"]', 'Select component', true);
    selectToSelect2('.task-param[param-name="arch"]', 'Select architecture', true);
    selectToSelect2('.task-param[param-name="package-include"]', 'Specify package(s)', true);
    selectToSelect2('.task-param[param-name="package-exclude"]', 'Specify package(s)', true);
    selectToSelect2('select.task-param[param-name="schedule-day"]', 'Select day(s)', true);
    selectToSelect2('select.task-param[param-name="schedule-reminder"]', 'Select reminder(s)', true);
    selectToSelect2('select.task-param[param-name="schedule-recipient"]', 'Select or add recipient(s)', true);

    /**
     *  Show / hide the necessary fields
     */
    newRepoFormPrintFields();
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/new';
$slidePanelTitle = 'NEW REPOSITORY';

include(ROOT . '/views/includes/slide-panel.inc.php');
