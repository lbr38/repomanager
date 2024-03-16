<?php ob_start(); ?>
       
<form class="task-form" autocomplete="off">
    <div class="task-form-params" repo-id="none" action="create">
        <table>
            <tr>
                <td>Package type</td>
                <td>
                    <?php
                    /**
                     *  Case where the server manages several different types of repo
                     */
                    if (RPM_REPO == 'true' and DEB_REPO == 'true') : ?>
                        <div class="switch-field">
                            <input type="radio" id="package-type_rpm" class="task-param" param-name="package-type" name="package-type" value="rpm" checked />
                            <label for="package-type_rpm">rpm</label>
                            <input type="radio" id="package-type_deb" class="task-param" param-name="package-type" name="package-type" value="deb" />
                            <label for="package-type_deb">deb</label>
                        </div>
                        <?php
                    elseif (RPM_REPO == 'true') : ?>
                        <div class="single-switch-field">
                            <input type="radio" id="package-type_rpm" class="task-param" param-name="package-type" name="package-type" value="rpm" checked />
                            <label for="package-type_rpm">rpm</label>
                        </div>
                        <?php
                    elseif (DEB_REPO == 'true') : ?>
                        <div class="single-switch-field">
                            <input type="radio" id="package-type_deb" class="task-param" param-name="package-type" name="package-type" value="deb" checked />
                            <label for="package-type_deb">deb</label>
                        </div>
                        <?php
                    endif ?>
                </td>
            </tr>
            <tr>
                <td class="td-30">Repo type</td>
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
                <td class="td-30">Source repo</td>
                <td>
                    <?php
                    if (RPM_REPO == 'true') : ?>
                        <select class="task-param" param-name="rpm-source" field-type="mirror rpm" package-type="rpm">
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
                        <select class="task-param" param-name="deb-source" field-type="mirror deb" package-type="deb">
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
                <td class="td-30" field-type="mirror rpm deb">
                    <span>Custom repo name</span>
                    <span class="lowopacity-cst">(optionnal)</span>
                </td>
                <td class="td-30" field-type="local rpm deb">Repo name</td>
                <td>
                    <input type="text" class="task-param" param-name="alias" package-type="all" />
                </td>
            </tr>
            <tr field-type="mirror local rpm">
                <td class="td-30">Release version</td>
                <td>
                    <select class="task-param" param-name="releasever" package-type="rpm" multiple>
                        <option value="7" <?php echo (RELEASEVER == 7) ? 'selected' : '' ?>>7 (Redhat 7 and derivatives)</option>
                        <option value="8" <?php echo (RELEASEVER == 8) ? 'selected' : '' ?>>8 (Redhat 8 and derivatives)</option>
                        <option value="9" <?php echo (RELEASEVER == 9) ? 'selected' : '' ?>>9 (Redhat 9 and derivatives)</option>
                    </select>
                </td>
            </tr>
            <tr field-type="mirror local deb">
                <td class="td-30">Distribution</td>
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
                <td class="td-30">Section</td>
                <td>
                    <select class="task-param" param-name="section" package-type="deb" multiple>
                        <option value="main">main</option>
                        <option value="contrib">contrib</option>
                        <option value="non-free">non-free</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="td-30">Point an environment</td>
                <td>
                    <select id="new-repo-target-env-select" class="task-param" param-name="targetEnv" package-type="all">
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
                <td class="td-30">
                    <span>Description</span>
                    <span class="lowopacity-cst">(optionnal)</span>
                </td>
                <td><input type="text" class="task-param" param-name="description" package-type="all" /></td>
            </tr>

            <?php
            /**
             *  Possibility to add to a group, if there is at least one group
             */
            if (!empty($newRepoFormGroupList)) : ?>
                <tr>
                    <td class="td-30">
                        <span>Add to group</span>
                        <span class="lowopacity-cst">(optionnal)</span>
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
                <td class="td-30">Check GPG signatures</td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="repoGpgCheck" type="checkbox" class="onoff-switch-input task-param" value="yes" param-name="gpg-check" package-type="all" checked />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>

            <tr field-type="mirror rpm deb">
                <td class="td-30">Sign with GPG</td>
                <td>
                    <label class="onoff-switch-label" field-type="mirror rpm">
                        <input name="repoGpgResign" type="checkbox" class="onoff-switch-input task-param type_rpm" value="yes" param-name="gpg-sign" package-type="rpm" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                    <label class="onoff-switch-label" field-type="mirror deb">
                        <input name="repoGpgResign" type="checkbox" class="onoff-switch-input task-param type_deb" value="yes" param-name="gpg-sign" package-type="deb" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>

            <tr field-type="mirror rpm deb">
                <td colspan="100%"><b>Advanced parameters</b></td>
            </tr>

            <tr field-type="mirror local rpm deb">
                <td class="td-30">Architecture</td>
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

            <!-- <tr field-type="mirror deb">
                <td class="td-30">Include translation</td>
                <td>
                    <select id="targetPackageTranslationSelect" class="task-param" param-name="targetPackageTranslation" package-type="deb" multiple>
                        <option value="">Select translation(s)...</option>
                        <option value="en" <?php //echo (in_array('en', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>en (english)</option>
                        <option value="fr" <?php //echo (in_array('fr', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>fr (french)</option>
                        <option value="de" <?php //echo (in_array('de', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>de (deutsch)</option>
                        <option value="it" <?php //echo (in_array('it', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>it (italian)</option>
                    </select>
                </td>
            </tr> -->

            <tr>
                <td colspan="100%"><b>Task scheduling</b></td>
            </tr>

            <tr>
                <td class="td-30">Schedule it</td>
                <td>
                    <label class="onoff-switch-label">
                        <input type="checkbox" id="task-schedule-btn" class="onoff-switch-input" value="yes" />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>

            <!-- Scheduling params -->
            <tr>
                <td class="td-30">Schedule type</td>
                <td>
                    <div class="switch-field">
                        <input type="radio" id="task-schedule-type-unique" class="task-param" param-name="schedule-type" name="task-schedule-type" checked />
                        <label for="task-schedule-type-unique">Unique task</label>
                        <input type="radio" id="task-schedule-type-recurrent" class="task-param" param-name="schedule-type" name="task-schedule-type" />
                        <label for="task-schedule-type-recurrent">Recurrent task</label>
                    </div>
                </td>
            </tr>

            <tr class="task-schedule-recurrent-input hide">
                <td class="td-10">Frequency</td>
                <td>
                    <select class="task-param" param-name="schedule-frequency">
                        <option value="">Select...</option>
                        <option value="every-hour">Hourly</option>
                        <option value="every-day">Daily</option>
                        <option value="every-week">Weekly</option>
                    </select>
                </td>
            </tr>

            <tr class="task-schedule-recurrent-day-input hide">
                <td class="td-10">Day(s)</td>
                <td>
                    <select class="task-param" param-name="schedule-day" multiple>
                        <option value="monday">Monday</option>
                        <option value="tuesday">Tuesday</option>
                        <option value="wednesday">Wednesday</option>
                        <option value="thursday">Thursday</option>
                        <option value="friday">Friday</option>
                        <option value="saturday">Saturday</option>
                        <option value="sunday">Sunday</option>
                    </select>
                </td>
            </tr>

            <tr class="task-schedule-unique-input">
                <td class="td-10">Date</td>
                <td><input type="date" class="task-param" param-name="schedule-date" /></td>
            </tr>

            <tr class="task-schedule-time-input">
                <td class="td-10">Time</td>
                <td><input type="time" class="task-param" param-name="schedule-time" /></td>
            </tr>

            <tr>
                <td class="td-10">Notify on task error</td>
                <td>
                    <label class="onoff-switch-label">
                        <input type="checkbox" class="onoff-switch-input task-param" param-name="notify-error" value="yes" checked />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>

            <tr>
                <td class="td-10">Notify on task success</td>
                <td>
                    <label class="onoff-switch-label">
                        <input type="checkbox" class="onoff-switch-input task-param" param-name="notify-success" value="yes" checked />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>

            <tr>
                <td class="td-10">Send a reminder</td>
                <td>
                    <select class="task-param" param-name="reminder" multiple>
                        <option value="1">1 day before</option>
                        <option value="2">2 days before</option>
                        <option value="3" selected>3 days before</option>
                        <option value="4">4 days before</option>
                        <option value="5">5 days before</option>
                        <option value="6">6 days before</option>
                        <option value="7" selected>7 days before</option>
                        <option value="8">8 days before</option>
                        <option value="9">9 days before</option>
                        <option value="10">10 days before</option>
                        <option value="15">15 days before</option>
                        <option value="20">20 days before</option>
                        <option value="25">25 days before</option>
                        <option value="30">30 days before</option>
                        <option value="35">35 days before</option>
                        <option value="40">40 days before</option>
                        <option value="45">45 days before</option>
                        <option value="50">50 days before</option>
                        <option value="55">55 days before</option>
                        <option value="60">60 days before</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Recipient(s)</td>
                <td>
                    <select class="task-param" param-name="reminder-recipient" multiple>
                        <?php
                        if (!empty(EMAIL_RECIPIENT)) {
                            foreach (EMAIL_RECIPIENT as $email) {
                                echo '<option value="' . $email . '" selected>' . $email . '</option>';
                            }
                        }
                        if (!empty($usersEmail)) {
                            foreach ($usersEmail as $email) {
                                if (!in_array($email, EMAIL_RECIPIENT)) {
                                    echo '<option value="' . $email . '">' . $email . '</option>';
                                }
                            }
                        } ?>
                    </select>
                </td>
            </tr>
        </table>
    </div>
    
    <br>
    <button class="btn-large-red">Execute now</button>
</form>

<script>
$(document).ready(function(){
    selectToSelect2('select.task-param[param-name="schedule-day"]', 'Select day(s)...', true);
    selectToSelect2('select.task-param[param-name="reminder"]', 'Select reminder...', true);
    selectToSelect2('select.task-param[param-name="reminder-recipient"]', 'Select or add recipients...', true);
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/new';
$slidePanelTitle = 'CREATE A NEW REPO';

include(ROOT . '/views/includes/slide-panel.inc.php');
