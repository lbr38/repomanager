<div class="reloadable-container" container="planifications/form">
    <?php
    if (IS_ADMIN) : ?>
        <!-- <h5>SCHEDULE A TASK</h5> -->

        <form id="newPlanForm" class="div-generic-blue" autocomplete="off">
            <table class="table-large">
                <tr>
                    <td>Type</td>
                    <td class="td-medium">
                        <div class="switch-field">
                            <input type="radio" id="addPlanType-plan" name="planType" value="plan" checked />
                            <label for="addPlanType-plan">Unique task</label>
                            <input type="radio" id="addPlanType-regular" name="planType" value="regular" />
                            <label for="addPlanType-regular">Recurrent task</label>
                        </div>
                    </td>
                </tr>

                <tr class="__regular_plan_input hide">
                    <td class="td-10">Frequency</td>
                    <td>
                        <select id="planFrequencySelect">
                            <option value="">Select...</option>
                            <option value="every-hour">Hourly</option>
                            <option value="every-day">Daily</option>
                            <option value="every-week">Weekly</option>
                        </select>
                    </td>
                </tr>

                <tr class="__regular_plan_input __regular_plan_day_input hide">
                    <td class="td-10">Day(s)</td>
                    <td>
                        <select id="planDayOfWeekSelect" multiple>
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

                <tr class="__plan_input">
                    <td class="td-10">Date</td>
                    <td><input id="addPlanDate" type="date" /></td>
                </tr>

                <tr class="__plan_hour_input">
                    <td class="td-10">Time</td>
                    <td><input id="addPlanTime" type="time" /></td>
                </tr>

                <tr>
                    <td class="td-10">Action</td>
                    <td>
                        <select id="planActionSelect">
                            <option>Select an action...</option>
                            <option value="update" id="updateRepoSelect">Update</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td class="td-10">Repo</td>
                    <td>
                        <select id="addPlanSnapId">
                            <option value="">Select a repo...</option>
                            <?php
                            if (!empty($reposList)) {
                                foreach ($reposList as $repo) {
                                    $snapId = $repo['snapId'];
                                    $repoName = $repo['Name'];
                                    $repoDist = $repo['Dist'];
                                    $repoSection = $repo['Section'];
                                    $repoDate = $repo['Date'];
                                    $repoDateFormatted = DateTime::createFromFormat('Y-m-d', $repoDate)->format('d-m-Y');
                                    $repoPackageType = $repo['Package_type'];
                                    $repoType = $repo['Type'];

                                    /**
                                     *  Si le repo est local alors on ne l'affiche pas dans la liste
                                     */
                                    if ($repoType == 'local') {
                                        continue;
                                    }

                                    /**
                                     *  On génère une <option> pour chaque repo
                                     */
                                    if ($repoPackageType == "rpm") {
                                        echo '<option value="' . $snapId . '" package-type="' . $repoPackageType . '">' . $repoName . ' ⟶ ' . $repoDateFormatted . '</option>';
                                    }
                                    if ($repoPackageType == "deb") {
                                        echo '<option value="' . $snapId . '" package-type="' . $repoPackageType . '"><span class="label-white">' . $repoName . ' ❯ ' . $repoDist . ' ❯ ' . $repoSection . '</span> ⟶ ' . $repoDateFormatted . '</option>';
                                    }
                                }
                            } ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td class="td-10">or Group</td>
                    <td>
                        <select id="addPlanGroupId">
                            <option value="">Select a group...</option>
                            <?php
                            if (!empty($groupsList)) {
                                foreach ($groupsList as $group) {
                                    $groupId = $group['Id'];
                                    $groupName = $group['Name'];
                                    echo '<option value="' . $groupId . '">' . $groupName . '</option>';
                                }
                            } ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td>Point an environment</td>
                    <td>
                        <select id="addPlanTargetEnv">
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

                <tr id="update-preview" class="hide">
                    <td colspan="100%">
                        <br><hr><br>
                        <p>The update will create a new snapshot for every selected repo:<br></p>
                        <span id="update-preview-date" class="label-black"></span><span id="update-preview-target-env">⟵</span>
                        <br><br>
                    </td>
                </tr>

                <tr class="__plan_gpg_input hide">
                    <td colspan="100%">
                        <hr>
                        <p><b>GPG params</b></p>
                    </td>
                </tr>
    
                <tr class="__plan_gpg_input hide">
                    <td class="td-10">Check GPG signatures</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input id="addPlanGpgCheck" type="checkbox" name="addPlanGpgCheck" class="onoff-switch-input" value="yes" checked />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>

                <tr class="__plan_gpg_input hide">
                    <td class="td-10">Sign with GPG</td>
                    <td>
                        <label class="onoff-switch-label">
                            <?php
                            $planGpgResign = 'yes';
                            /**
                             *  Si les deux constantes suivantes valent 'no' alors la signature avec GPG sera désactivée par défaut
                             */
                            if (RPM_SIGN_PACKAGES == 'false' and DEB_SIGN_REPO == 'false') {
                                $planGpgResign = 'no';
                            } ?>
                            <input id="addPlanGpgResign" type="checkbox" name="addPlanGpgResign" class="onoff-switch-input" value="yes" <?php echo ($planGpgResign == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>

                <tr class="__plan_difference_input hide">
                    <td colspan="100%">
                        <hr>
                        <p><b>Mirroring params</b></p>
                    </td>
                </tr>

                <tr class="__plan_difference_input hide">
                    <td class="td-10" title="Selected snapshot content will be copied to the new snapshot before syncing. Then only the new changed packages will be synced from source repository. Can significantly reduce syncing duration on large repos.">Only sync the difference</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input id="onlySyncDifference" type="checkbox" name="onlySyncDifference" class="onoff-switch-input" value="yes" checked />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>

                <tr>
                    <td colspan="100%">
                        <hr><p><b>Mail notifications</b></p>
                    </td>
                </tr>
                <tr>
                    <td>Recipient(s)</td>
                    <td>
                        <select id="addPlanMailRecipient" multiple>
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
                <tr class="__plan_input __plan_input_reminder">
                    <td class="td-10">Send a reminder</td>
                    <td>
                        <select id="planReminderSelect" name="addPlanReminder[]" multiple>
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
                    <td class="td-10">On plan error</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input id="addPlanNotificationOnError" name="addPlanNotificationOnError" type="checkbox" class="onoff-switch-input" value="yes" checked />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="td-10">On plan success</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input id="addPlanNotificationOnSuccess" type="checkbox" class="onoff-switch-input" value="yes" checked />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td colspan="100%"><button type="submit" class="btn-large-green">Plan</button></td>
                </tr>
            </table>
        </form>
        <?php
    endif; ?>
</div>

<script>
$(document).ready(function(){
    // idToSelect2('#planActionSelect', 'Select action...', true);
    idToSelect2('#planReminderSelect', 'Select reminder...', true);
    idToSelect2('#planDayOfWeekSelect', 'Select day(s)...', true);
    idToSelect2('#addPlanMailRecipient', 'Select recipients...', true);
});
</script>