<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <h5>QUEUED TASKS</h5>

        <?php
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container plan-details-btn pointer" plan-id="<?= $item['Id'] ?>" title="Show planification details">
                <div>
                    <?php
                    if ($item['Action'] == 'update') {
                        echo '<img class="icon" src="/assets/icons/update.svg" title="Action: ' . $item['Action'] . '" />';
                    } ?>
                </div>

                <div>
                    <span>
                        <?php
                        if ($item['Type'] == 'plan') {
                            echo 'Planned on <b>' . DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') . ' ' . $item['Time'] . '</b>';
                        }

                        if ($item['Type'] == 'regular') {
                            if ($item['Frequency'] == 'every-hour') {
                                echo 'Hourly</b>';
                            }
                            if ($item['Frequency'] == 'every-day') {
                                echo 'Daily at <b>' . $item['Time'] . '</b>';
                            }
                            if ($item['Frequency'] == 'every-week') {
                                echo 'Weekly';
                            }
                        } ?>
                    </span>
                </div>

                <div class="wordbreakall">
                    <?php
                    /**
                     *  If the planification is about a group, we get its name from its Id
                     */
                    if (!empty($item['Id_group'])) {
                        /**
                         *  Check if the group still exists (it may have been deleted in the meantime)
                         */
                        if ($mygroup->existsId($item['Id_group']) === false) {
                            echo '<span class="label-red">Unknown group (deleted)</span>';
                        } else {
                            echo '<span class="label-white">' . $mygroup->getNameById($item['Id_group']) . ' </span> group';
                        }
                    }

                    /**
                     *  If the planification is about a repo, we get its infos from its Id
                     */
                    if (!empty($item['Id_snap'])) :
                        /**
                         *  Check if the repo still exists (it may have been deleted in the meantime)
                         */
                        if ($myrepo->existsSnapId($item['Id_snap']) === false) {
                            $repo = '<span class="label-red">Unknown repo (deleted)</span>';
                        } else {
                            /**
                             *  Get all infos about the repo
                             */
                            $myrepo->getAllById('', $item['Id_snap'], '');

                            /**
                             *  Format
                             */
                            if (!empty($myrepo->getDist()) and !empty($myrepo->getSection())) {
                                $repo = '<span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>';
                            } else {
                                $repo = '<span class="label-white">' . $myrepo->getName() . '</span>';
                            }
                        }

                        echo $repo;
                    endif ?>
                </div>

                <div class="flex align-item-center justify-end">
                    <?php
                    if ($item['Status'] == 'queued') {
                        if ($item['Type'] == 'regular') {
                            if (IS_ADMIN) {
                                echo '<img class="disablePlanBtn icon-lowopacity" plan-id="' . $item['Id'] . '" title="Disable recurrent plan execution" src="/assets/icons/disabled.svg" />';
                            }
                        }
                    }

                    if ($item['Status'] != 'running') {
                        if (IS_ADMIN) {
                            echo '<img class="deletePlanBtn icon-lowopacity" plan-id="' . $item['Id'] . '" plan-type="' . $item['Type'] . '" title="Delete plan" src="/assets/icons/delete.svg" />';
                        }
                    }

                    if ($item['Status'] == 'queued') {
                        echo '<img src="/assets/icons/greencircle.png" class="icon-small" title="Plan is enabled" />';
                    }

                    if ($item['Status'] == 'running') {
                        echo '<span>running</span><img src="/assets/images/loading.gif" class="icon" title="Plan is currently running" />';
                    }

                    if ($item['Status'] == 'disabled') {
                        if (IS_ADMIN) {
                            echo '<img class="enablePlanBtn icon-lowopacity" plan-id="' . $item['Id'] . '" title="Enable plan" src="/assets/icons/enabled.svg" />';
                        }

                        echo '<img src="/assets/icons/yellowcircle.png" class="icon-small" title="Plan is disabled" />';
                    } ?>
                </div>
            </div>
            
            <div class="hide plan-info-div margin-bottom-5" plan-id="<?= $item['Id'] ?>">
                <?php
                if ($item['Action'] == 'update') {
                    if (!empty($item['Id_group'])) {
                        if ($mygroup->existsId($item['Id_group']) === false) {
                            echo '<p>Update repos of <span class="label-red">Unknown group (deleted)</span></p>';
                        } else {
                            echo '<p>Update repos of the <span class="label-white">' . $mygroup->getNameById($item['Id_group']) . ' </span> group</p>';
                        }
                    } else {
                        echo '<p>Update ' . $repo . ' repo</p>';
                    }
                }

                echo '<br>';

                if ($item['Status'] == 'disabled') {
                    echo '<p class="yellowtext"<b>This plan execution is disabled</b></p><br>';
                }

                /**
                 *  Print the days where the recurrent planification is active
                 */
                if ($item['Type'] == 'regular') :
                    if (!empty($item['Day'])) : ?>
                        <div>
                            <span>Day(s)</span>
                            <span>
                                <?php
                                $item['Day'] = explode(',', $item['Day']);

                                foreach ($item['Day'] as $day) {
                                    echo $day . '<br>';
                                } ?>
                            </span>
                        </div>
                        <?php
                    endif;
                endif;

                if (!empty($item['Time'])) : ?>
                    <div>
                        <span>Time</span>
                        <span><?= $item['Time'] ?></span>
                    </div>
                    <?php
                endif;

                if ($item['Action'] == 'update') : ?>
                    <div>
                        <span>Check GPG signatures</span>
                        <?php
                        if ($item['Gpgcheck'] == 'yes') {
                            echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                        } else {
                            echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                        } ?>
                    </div>

                    <div>
                        <span>Sign with GPG</span>
                        <?php
                        if ($item['Gpgresign'] == 'yes') {
                            echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                        } else {
                            echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                        } ?>
                    </div>

                    <div>
                        <span>Only sync the difference</span>
                        <?php
                        if ($item['OnlySyncDifference'] == 'yes') {
                            echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                        } else {
                            echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                        } ?>
                    </div>
                    <?php
                endif ?>

                <hr>

                <div>
                    <span>Reminder</span>
                    <span>
                        <?php
                        if (empty($item['Reminder'])) {
                            echo 'None';
                        } else {
                            $item['Reminder'] = explode(',', $item['Reminder']);

                            foreach ($item['Reminder'] as $reminder) {
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
                    if ($item['Notification_error'] == 'yes') {
                        echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                    } else {
                        echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                    } ?>
                </div>

                <div>
                    <span>Notification on success</span>
                    <?php
                    if ($item['Notification_success'] == 'yes') {
                        echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                    } else {
                        echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                    } ?>
                </div>

                <?php
                if (!empty($item['Mail_recipient'])) : ?>
                    <div>
                        <span>Contact</span>
                        <span>
                            <?php
                            $item['Mail_recipient'] = explode(',', $item['Mail_recipient']);

                            foreach ($item['Mail_recipient'] as $recipient) {
                                if (!empty($recipient)) {
                                    echo $recipient . '<br>';
                                }
                            } ?>
                        </span>
                    </div>
                    <?php
                endif;
                if (!empty($item['Logfile'])) {
                    echo '<div><span>Log</span><span><a href="/run?view-logfile=' . $item['Logfile'] . '"><button class="btn-small-green"><b>Check log</b></button></a></span></div>';
                } ?>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex column-gap-10 justify-end">
            <?php
            if ($reloadableTableOffset > 0) {
                echo '<div class="reloadable-table-previous-btn btn-small-green">Previous</div>';
            }

            if ($reloadableTableCurrentPage < $reloadableTableTotalPages) {
                echo '<div class="reloadable-table-next-btn btn-small-green">Next</div>';
            } ?>
        </div>

        <?php
    endif ?>
</div>

<br>