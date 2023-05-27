<div class="reloadable-container" container="plans/planned">
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
                                        echo '<img class="icon" src="assets/icons/update.svg" title="Operation type: ' . $planAction . '" />';
                                    } else {
                                        echo '<img class="icon" src="assets/icons/link.svg" title="Operation type: ' . $planAction . '" />';
                                    } ?>
                                </td>
                                <td class="td-10">
                                    <?php
                                    /**
                                     *  Affichage du type de planification
                                     */
                                    if ($planType == "plan") {
                                        echo 'Planed on <b>' . $planDate . ' ' . $planTime . '</b>';
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
                                <td class="wordbreakall">
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
                                        <img class="planDetailsBtn icon-lowopacity" plan-id="<?= $planId ?>" title="Show details" src="assets/icons/search.svg" />
                                    </span>
                                    <span>
                                        <?php
                                        if ($planStatus != "running") {
                                            echo '<img class="deletePlanBtn icon-lowopacity" plan-id="' . $planId . '" plan-type="' . $planType . '" title="Delete plan" src="assets/icons/delete.svg" />';
                                        }
                                        if ($planStatus == "queued") {
                                            if ($planType == 'regular') {
                                                echo '<img class="disablePlanBtn icon-lowopacity" plan-id="' . $planId . '" title="Disable recurrent plan execution" src="assets/icons/disabled.svg" />';
                                            }
                                            echo '<img src="assets/icons/greencircle.png" class="icon-small" title="Plan is enabled" />';
                                        }
                                        if ($planStatus == "running") {
                                            echo 'running<img src="assets/images/loading.gif" class="icon" title="Plan is currently running" />';
                                        }
                                        if ($planStatus == "disabled") {
                                            echo '<img class="enablePlanBtn icon-lowopacity" plan-id="' . $planId . '" title="Enable plan" src="assets/icons/enabled.svg" />';
                                            echo '<img src="assets/icons/yellowcircle.png" class="icon-small" title="Plan is disabled" />';
                                        } ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                
                    <div class="hide planInfo" plan-id="<?= $planId ?>">
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

                        if ($planStatus == 'disabled') {
                            echo '<p class="yellowtext"<b>This plan execution is disabled</b></p><br>';
                        }

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
                                    echo '<span><img src="assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                } else {
                                    echo '<span><img src="assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                } ?>
                            </div>
                            <div>
                                <span>Sign with GPG</span>
                                <?php
                                if ($planGpgResign == "yes") {
                                    echo '<span><img src="assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                } else {
                                    echo '<span><img src="assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                                } ?>
                            </div>
                            <div>
                                <span>Only sync the difference</span>
                                <?php
                                if ($planOnlySyncDifference == "yes") {
                                    echo '<span><img src="assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                                } else {
                                    echo '<span><img src="assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
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
                                echo '<span><img src="assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                            } else {
                                echo '<span><img src="assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                            } ?>
                        </div>
                        <div>
                            <span>Notification on success</span>
                            <?php
                            if ($planNotificationOnSuccess == "yes") {
                                echo '<span><img src="assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                            } else {
                                echo '<span><img src="assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
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
                            echo '<div><span>Log</span><span><a href="/run?view-logfile=' . $planLogfile . '"><button class="btn-small-green"><b>Check log</b></button></a></span></div>';
                        } ?>
                    </div>
                </div>
                <?php
            endforeach; ?>
        </div>
        <?php
    endif ?>
</div>