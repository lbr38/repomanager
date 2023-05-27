<div class="reloadable-container" container="plans/history">
    <?php
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
                                    if ($planStatus == "done") {
                                        echo '<img class="icon-small" src="assets/icons/greencircle.png" title="Plan completed." />';
                                    } elseif ($planStatus == "error") {
                                        echo '<img class="icon-small" src="assets/icons/redcircle.png" title="Plan has failed." />';
                                    } elseif ($planStatus == "stopped") {
                                        echo '<img class="icon-small" src="assets/icons/redcircle.png" title="Plan stopped by the user." />';
                                    } ?>
                                </td>
                                <td class="td-small"><b><?= $planDate ?> <?= $planTime ?></b></td>
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
                                        <img class="planDetailsBtn icon-lowopacity" plan-id="<?= $planId ?>" title="Show details." src="assets/icons/search.svg" />
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="hide planInfo" plan-id="<?= $planId ?>">
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
                        endif; ?>
                        
                        <div>
                            <?php
                            if (!empty($planLogfile)) {
                                echo '<span>Log</span>';
                                echo "<span><a href='/run?view-logfile=$planLogfile'><button class='btn-small-green'><b>Check log</b></button></a></></span>";
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
