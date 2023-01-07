<?php
include_once(ROOT . '/views/includes/display.inc.php');

if (IS_ADMIN) :
    include_once(ROOT . '/views/includes/operation.inc.php');
    include_once(ROOT . '/views/includes/manage-groups.inc.php');
    include_once(ROOT . '/views/includes/manage-sources.inc.php');
endif ?>

<section class="mainSectionRight">
    <div id="planDiv">
        <?php
        if (IS_ADMIN) : ?>
            <h3>PLAN A TASK</h3>
            <?php
        endif;

        /**
         *  Print planned tasks if there are
         */
        if (!empty($planList)) : ?>
            <div class="div-generic-blue">

                <h5>PLANNED TASKS</h5>

                <?php
                foreach ($planList as $plan) :
                    $planId = $plan['Id'];
                    $planType = $plan['Type'];
                    $planGroup = '';
                    $planDate = '';
                    $planTime = '';
                    $planReminder = 'None';

                    if (!empty($plan['Day'])) {
                        $planDay = $plan['Day'];
                    }
                    if (!empty($plan['Frequency'])) {
                        $planFrequency = $plan['Frequency'];
                    }
                    if (!empty($plan['Date'])) {
                        $planDate = DateTime::createFromFormat('Y-m-d', $plan['Date'])->format('d-m-Y');
                    }
                    if (!empty($plan['Time'])) {
                        $planTime = $plan['Time'];
                    }

                    $planAction = $plan['Action'];
                    $planGroupId = $plan['Id_group'];
                    $planSnapId = $plan['Id_snap'];
                    $planGpgCheck = $plan['Gpgcheck'];
                    $planGpgResign = $plan['Gpgresign'];
                    $planOnlySyncDifference = $plan['OnlySyncDifference'];
                    $planMailRecipient = $plan['Mail_recipient'];
                    $planNotificationOnError = $plan['Notification_error'];
                    $planNotificationOnSuccess = $plan['Notification_success'];
                    $planStatus = $plan['Status'];
                    $planLogfile = $plan['Logfile'];

                    if (!empty($plan['Reminder'])) {
                        $planReminder = $plan['Reminder'];
                    } ?>

                    <div class="header-container">
                        <div class="header-blue">
                            <table>
                                <tr>
                                    <td class="td-10">
                                        <?php
                                        if ($planAction == "update") {
                                            echo '<img class="icon" src="resources/icons/update.svg" title="Operation type: ' . $planAction . '" />';
                                        } else {
                                            echo '<img class="icon" src="resources/icons/link.svg" title="Operation type: ' . $planAction . '" />';
                                        } ?>
                                    </td>

                                    <td class="td-small">
                                        <?php
                                        /**
                                         *  Affichage du type de planification
                                         */
                                        if ($planType == "plan") {
                                            echo 'Planed on <b>' . $planDate . '</b> at <b>' . $planTime . '</b>';
                                        }
                                        if ($planType == "regular") {
                                            if ($planFrequency == "every-hour") {
                                                echo 'Hourly</b>';
                                            }
                                            if ($planFrequency == "every-day") {
                                                echo 'Daily at <b>' . $planTime . '</b>';
                                            }
                                            if ($planFrequency == "every-week") {
                                                echo 'Weekly';
                                            }
                                        } ?>
                                    </td>

                                    <td>
                                        <?php
                                        /**
                                         *  Si la planification traite un groupe, on récupère son nom à partir de son Id
                                         */
                                        if (!empty($planGroupId)) {
                                            /**
                                             *  On vérifie que le groupe spécifié existe toujours (il a peut être été supprimé entre temps)
                                             */
                                            if ($mygroup->existsId($planGroupId) === false) {
                                                echo 'Unknown group (deleted)';
                                            } else {
                                                echo '<span class="label-white">' . $mygroup->getNameById($planGroupId) . ' </span> group';
                                            }
                                        }

                                        /**
                                         *  Si la planification traite un repo, on récupère ses informations à partir de son Id
                                         */
                                        if (!empty($planSnapId)) :
                                            /**
                                             *  On vérifie que le repo spécifié existe toujours (il a peut être été supprimé entre temps)
                                             */
                                            if ($myrepo->existsSnapId($planSnapId) === false) {
                                                $repo = "Unknown repo (deleted)";
                                            } else {
                                                /**
                                                 *  Récupération de toutes les infos concernant le repo
                                                 */
                                                $myrepo->getAllById('', $planSnapId, '');
                                                $planName = $myrepo->getName();
                                                $planDist = $myrepo->getDist();
                                                $planSection = $myrepo->getSection();
                                                $planDate = $myrepo->getDateFormatted();

                                                /**
                                                 *  Formatage
                                                 */
                                                if (!empty($myrepo->getDist()) and !empty($myrepo->getSection())) {
                                                    $planDist = $myrepo->getDist();
                                                    $planSection = $myrepo->getSection();
                                                    $repo = '<span class="label-white">' . $planName . ' ❯ ' . $planDist . ' ❯ ' . $planSection . '</span>';
                                                } else {
                                                    $repo = '<span class="label-white">' . $planName . '</span>';
                                                }
                                                $planDate = '<span class="label-white">' . $planDate . '</span>';
                                            }
                                            echo $repo;
                                        endif ?>
                                    </td>

                                    <td class="td-fit">
                                        <span>
                                            <img class="planDetailsBtn icon-lowopacity" plan-id="<?= $planId ?>" title="Show details." src="resources/icons/search.svg" />
                                        </span>
                                        <span>
                                            <?php
                                            if ($planStatus == "queued") {
                                                echo '<img class="deletePlanButton icon-lowopacity" plan-id="' . $planId . '" plan-type="' . $planType . '" title="Delete plan." src="resources/icons/bin.svg" />';
                                            }
                                            if ($planStatus == "running") {
                                                echo 'running<img src="resources/images/loading.gif" class="icon" title="Plan is currently running." />';
                                            } ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    
                        <div class="hide detailsDiv" plan-id="<?= $planId ?>">
                            <?php
                            /**
                             *  Affichage de l'action
                             */
                            if ($planAction == "update") {
                                if (!empty($planGroupId)) {
                                    if ($mygroup->existsId($planGroupId) === false) {
                                        echo '<p>Update repos of Unknown group (deleted)</p>';
                                    } else {
                                        echo '<p>Update repos of the <span class="label-white">' . $mygroup->getNameById($planGroupId) . ' </span> group</p>';
                                    }
                                } else {
                                    echo '<p>Update ' . $repo . ' repo</p>';
                                }
                            }

                            echo '<br>';

                            /**
                             *  Affichage des jours où la planification récurrente est active
                             */
                            if ($planType == "regular") :
                                if (!empty($planDay)) : ?>
                                    <div>
                                        <span>Day(s)</span>
                                        <span>
                                            <?php
                                            $planDay = explode(',', $planDay);

                                            foreach ($planDay as $day) :
                                                if ($day == "monday") {
                                                    echo 'monday';
                                                }
                                                if ($day == "tuesday") {
                                                    echo 'tuesday';
                                                }
                                                if ($day == "wednesday") {
                                                    echo 'wednesday';
                                                }
                                                if ($day == "thursday") {
                                                    echo 'thursday';
                                                }
                                                if ($day == "friday") {
                                                    echo 'friday';
                                                }
                                                if ($day == "saturday") {
                                                    echo 'saturday';
                                                }
                                                if ($day == "sunday") {
                                                    echo 'sunday';
                                                }
                                                echo '<br>';
                                            endforeach ?>
                                        </span>
                                    </div>
                                    <?php
                                endif;
                            endif;

                            /**
                             *  Affichage de l'heure
                             */
                            if (!empty($planTime)) : ?>
                                <div>
                                    <span>Time</span>
                                    <span><?= $planTime ?></span>
                                </div>
                                <?php
                            endif;

                            if ($planAction == "update") : ?>
                                <div>
                                    <span>Check GPG signatures</span>
                                    <?php
                                    if ($planGpgCheck == "yes") {
                                        echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                    } else {
                                        echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                    } ?>
                                </div>

                                <div>
                                    <span>Sign with GPG</span>
                                    <?php
                                    if ($planGpgResign == "yes") {
                                        echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                    } else {
                                        echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                    } ?>
                                </div>

                                <div>
                                    <span>Only sync the difference</span>
                                    <?php
                                    if ($planOnlySyncDifference == "yes") {
                                        echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                    } else {
                                        echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                    } ?>
                                </div>

                                <?php
                            endif ?>

                            <hr>

                            <div>
                                <span>Reminder</span>
                                <span>
                                    <?php
                                    if ($planReminder == 'None') {
                                        echo 'None';
                                    } else {
                                        $planReminder = explode(',', $planReminder);
                                        foreach ($planReminder as $reminder) {
                                            if (!empty($reminder)) {
                                                echo $reminder . ' days before<br>';
                                            }
                                        }
                                    } ?>
                                </span>
                            </div>

                            <div>
                                <span>Notification on error</span>
                                <?php
                                if ($planNotificationOnError == "yes") {
                                    echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                } else {
                                    echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                } ?>
                            </div>

                            <div>
                                <span>Notification on success</span>
                                <?php
                                if ($planNotificationOnSuccess == "yes") {
                                    echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                } else {
                                    echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                } ?>
                            </div>

                            <?php
                            if (!empty($planMailRecipient)) : ?>
                                <div>
                                    <span>Contact</span>
                                    <span>
                                        <?php
                                        $planMailRecipient = explode(',', $planMailRecipient);
                                        foreach ($planMailRecipient as $recipient) {
                                            if (!empty($recipient)) {
                                                echo $recipient . '<br>';
                                            }
                                        } ?>
                                    </span>
                                </div>
                                <?php
                            endif;
                            if (!empty($planLogfile)) {
                                echo '<div><span>Log</span><span><a href="/run?logfile=' . $planLogfile . '"><button class="btn-small-green"><b>Check log</b></button></a></span></div>';
                            } ?>
                        </div>
                    </div>
                    <?php
                endforeach; ?>
            </div>
            <?php
        endif;

        if (IS_ADMIN) : ?>
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
                                <option></option>
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
                                /**
                                 *  Récupération de la liste des repos qui possèdent un environnement DEFAULT_ENV
                                 */
                                $reposList = $myrepo->listForPlan();

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
                                $groupsList = $mygroup->listAll();

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
                        <td class="td-10" title="Selected snapshot content will be copied to the new snapshot before syncing. Then only the new changed packages will be synced from source repository. Can significantly reduce syncing duration on large repos.">Only sync the difference</td>
                        <td>
                            <label class="onoff-switch-label">
                                <input id="onlySyncDifference" type="checkbox" name="onlySyncDifference" class="onoff-switch-input" value="yes" />
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
                        <td><input type="email" id="addPlanMailRecipient" placeholder="Mails addresses seperated by a comma." value="<?= EMAIL_DEST ?>" multiple /></td>
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
        endif;

        /**
         *  Affichage des planifications terminées si il y en a
         */
        $plansDone = $myplan->listDone();

        if (!empty($plansDone)) : ?>
            <h3>PLAN HISTORY</h3>
            <div class="div-generic-blue">
                <?php
                foreach ($plansDone as $plan) :
                    $planId                    = $plan['Id'];
                    $planDay                   = $plan['Day'];
                    $planDate                  = DateTime::createFromFormat('Y-m-d', $plan['Date'])->format('d-m-Y');
                    $planTime                  = $plan['Time'];
                    $planAction                = $plan['Action'];
                    $planGroupId               = $plan['Id_group'];
                    $planSnapId                = $plan['Id_snap'];
                    $planGpgCheck              = $plan['Gpgcheck'];
                    $planGpgResign             = $plan['Gpgresign'];
                    $planOnlySyncDifference    = $plan['OnlySyncDifference'];
                    $planStatus                = $plan['Status'];
                    $planError                 = $plan['Error'];
                    $planMailRecipient         = $plan['Mail_recipient'];
                    $planNotificationOnError   = $plan['Notification_error'];
                    $planNotificationOnSuccess = $plan['Notification_success'];
                    $planLogfile               = $plan['Logfile'];
                    if (!empty($plan['Reminder'])) {
                        $planReminder  = $plan['Reminder'];
                    } else {
                        $planReminder = 'None';
                    }
                    if (empty($planDate)) {
                        $planDate = '?';
                    }
                    if (empty($planTime)) {
                        $planTime = '?';
                    }
                    if (empty($planAction)) {
                        $planAction = '?';
                    }
                    if (empty($planGpgCheck)) {
                        $planGpgCheck = '?';
                    }
                    if (empty($planGpgResign)) {
                        $planGpgResign = '?';
                    }
                    if (empty($planReminder)) {
                        $planReminder = '?';
                    }
                    if (empty($planStatus)) {
                        $planStatus = '?';
                    } ?>
                    <div class="header-container">
                        <div class="header-blue">
                            <table>
                                <tr>
                                    <td class="td-10">
                                        <?php
                                        if ($planAction == "update") {
                                            echo '<img class="icon" src="resources/icons/update.svg" title="Operation type: ' . $planAction . '" />';
                                        } else {
                                            echo '<img class="icon" src="resources/icons/link.svg" title="Operation type: ' . $planAction . '" />';
                                        } ?>
                                    </td>

                                    <td class="td-small"><b><?= $planDate ?></b> at <b><?= $planTime ?></b></td>

                                    <td>
                                        <?php
                                        /**
                                         *  Affichage du repo ou du groupe
                                         */
                                        if (!empty($planGroupId)) {
                                            if ($mygroup->existsId($planGroupId) === false) {
                                                $planGroup = "Unknown group (deleted)";
                                            } else {
                                                $planGroup = '<span class="label-white">' . $mygroup->getNameById($planGroupId) . '</span> group';
                                            }
                                            echo $planGroup;
                                        }
                                        if (!empty($planSnapId)) {
                                            /**
                                             *  Récupération de toutes les infos concernant le repo
                                             */
                                            $myrepo->getAllById('', $planSnapId, '');
                                            $planName = $myrepo->getName();
                                            $planDist = $myrepo->getDist();
                                            $planSection = $myrepo->getSection();

                                            /**
                                             *  Formatage
                                             */
                                            if (!empty($planDist) and !empty($planSection)) {
                                                $repo = '<span class="label-white">' . $planName . ' ❯ ' . $planDist . ' ❯ ' . $planSection . '</span>';
                                            } else {
                                                $repo = '<span class="label-white">' . $planName . '</span>';
                                            }
                                            echo $repo;
                                        } ?>
                                    </td>

                                    <td class="td-fit">
                                        <span>
                                            <img class="planDetailsBtn icon-lowopacity" plan-id="<?= $planId ?>" title="Show details." src="resources/icons/search.svg" />
                                        </span>
                                        <span>
                                            <?php
                                            /**
                                             *  Affichage d'une pastille verte ou rouge en fonction du status de la planification
                                             */
                                            if ($planStatus == "done") {
                                                echo '<img class="icon-small" src="resources/icons/greencircle.png" title="Plan completed." />';
                                            } elseif ($planStatus == "error") {
                                                echo '<img class="icon-small" src="resources/icons/redcircle.png" title="Plan has failed." />';
                                            } elseif ($planStatus == "stopped") {
                                                echo '<img class="icon-small" src="resources/icons/redcircle.png" title="Plan stopped by the user." />';
                                            } ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="hide detailsDiv" plan-id="<?= $planId ?>">
                            <?php
                            /**
                             *  Si la planification est en erreur alors on affiche le message d'erreur
                             */
                            if ($planStatus == "error") {
                                echo "<p>$planError</p>";
                            }
                            if ($planAction == "update") : ?>
                                <div>
                                    <span>Check GPG signatures</span>
                                    <?php
                                    if ($planGpgCheck == "yes") {
                                        echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                    } else {
                                        echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                    } ?>
                                </div>

                                <div>
                                    <span>Sign with GPG</span>
                                    <?php
                                    if ($planGpgResign == "yes") {
                                        echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                    } else {
                                        echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                    } ?>
                                </div>

                                <div>
                                    <span>Only sync the difference</span>
                                    <?php
                                    if ($planOnlySyncDifference == "yes") {
                                        echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                    } else {
                                        echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                    } ?>
                                </div>
                                <?php
                            endif; ?>
                            <div>
                                <span>Reminders</span>
                                <span>
                                    <?php
                                    if ($planReminder == 'None') {
                                        echo 'None';
                                    } else {
                                        $planReminder = explode(',', $planReminder);
                                        foreach ($planReminder as $reminder) {
                                            echo "$reminder days before<br>";
                                        }
                                    } ?>
                                </span>
                            </div>
                            
                            <div>
                                <span>Notification on error</span>
                                <?php
                                if ($planNotificationOnError == "yes") {
                                    echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                } else {
                                    echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                } ?>
                            </div>
                            
                            <div>
                                <span>Notification on success</span>
                                <?php
                                if ($planNotificationOnSuccess == "yes") {
                                    echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                } else {
                                    echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                } ?>
                            </div>
                            <?php
                            if (!empty($planMailRecipient)) : ?>
                                <div>
                                    <span>Contact</span>
                                    <span>
                                        <?php
                                        $planMailRecipient = explode(',', $planMailRecipient);
                                        foreach ($planMailRecipient as $recipient) {
                                            if (!empty($recipient)) {
                                                echo $recipient . '<br>';
                                            }
                                        } ?>
                                    </span>
                                </div>
                                <?php
                            endif; ?>
                            
                            <div>
                                <?php
                                if (!empty($planLogfile)) {
                                    echo '<span>Log</span>';
                                    echo "<span><a href='/run?logfile=$planLogfile'><button class='btn-small-green'><b>Check log</b></button></a></></span>";
                                } ?>
                            </div>
                        </div>
                    </div>
                    <?php
                endforeach; ?>
            </div>
            <?php
        endif; ?>
    </div>
</section>

<section class="mainSectionLeft">
    <div class="reposList">
        <?php include_once(ROOT . '/views/includes/repos-list-container.inc.php'); ?>
    </div>
</section>

<script>
$(document).ready(function(){

    var selectDateName = '#addPlanDate';
    var dateSpan = '#update-preview-date';
    var selectEnvName = '#addPlanTargetEnv';
    var envSpan = '#update-preview-target-env';

    function printDate() {
        /**
         *  Récupération de la date sélectionnée dans la liste
         */
        var selectValue = $(selectDateName).val();

        /**
         *  Si aucune date n'a été selectionnée par l'utilisateur alors on n'affiche rien 
         */
        if (selectValue == "") {
            $("#update-preview").hide();
        
        /**
         *  Sinon on affiche l'environnement qui pointe vers le nouveau snapshot qui sera créé
         */
        } else {
            $("#update-preview").css('display', 'table-row');
            $(dateSpan).html(selectValue);
        }
    }

    function printEnv() {
        /**
         *  Nom du dernier environnement de la chaine
         */
        var lastEnv = '<?=LAST_ENV?>';

        /**
         *  Récupération de l'environnement sélectionné dans la liste
         */
        var selectValue = $(selectEnvName).val();
        
        /**
         *  Si l'environnement correspond au dernier environnement de la chaine alors il sera affiché en rouge
         */
        if (selectValue == lastEnv) {
            var envSpanClass = 'last-env';

        } else {            
            var envSpanClass = 'env';
        }

        /**
         *  Si aucun environnement n'a été selectionné par l'utilisateur alors on n'affiche rien 
         */
        if (selectValue == "") {
            $(envSpan).html('');
        
        /**
         *  Sinon on affiche l'environnement qui pointe vers le nouveau snapshot qui sera créé
         */
        } else {
            $(envSpan).html('⟵<span class="'+envSpanClass+'">'+selectValue+'</span>');
        }
    }

    printDate();
    printEnv();

    $(document).on('change',selectDateName+','+selectEnvName,function(){
        printDate();
        printEnv();
  
    }).trigger('change');
});
</script>