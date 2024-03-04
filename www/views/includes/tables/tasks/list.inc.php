<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        $currentPlanId = 0;

        foreach ($reloadableTableContent as $item) :
            $tableTitle = '';

            /**
             *  Retrieve task parameters
             */
            $taskRawParams = json_decode($item['Raw_params'], true);

            /**
             *  If the current task item was made in a scheduled task, we display the scheduled task header
             */
            if ($item['Type'] == 'scheduled') {
                if ($item['Status'] == 'scheduled') {
                    $headerColor = 'header-light-blue';
                } else {
                    $headerColor = '';
                }
            } else {
                $headerColor = '';
            }

            $tableClass = 'table-container ' . $headerColor;

            /**
             *  If task has a logfile, we add the pointer class to the table container
             */
            if (!empty($item['Logfile'])) {
                $tableClass .= ' pointer show-logfile-btn';
                $tableTitle = 'View task log';
            } ?>

            <div class="<?= $tableClass ?>" logfile="<?= $item['Logfile'] ?>" title="<?= $tableTitle ?>">
                <div>
                    <?php
                    if ($taskRawParams['action'] == 'create') {
                        $icon = 'plus';
                        $actionTitle = 'New repository';
                    }

                    if ($taskRawParams['action'] == 'update') {
                        $icon = 'update';
                        $actionTitle = 'Update repository';
                    }

                    if ($taskRawParams['action'] == 'rebuild') {
                        $icon = 'update';
                        $actionTitle = 'Rebuild metadata';
                    }

                    if ($taskRawParams['action'] == 'env') {
                        $icon = 'link';
                        $actionTitle = 'Point an environment';
                    }

                    if ($taskRawParams['action'] == 'duplicate') {
                        $icon = 'duplicate';
                        $actionTitle = 'Duplicate repository';
                    }

                    if ($taskRawParams['action'] == 'delete') {
                        $icon = 'delete';
                        $actionTitle = 'Delete repository';
                    }

                    if ($taskRawParams['action'] == 'removeEnv') {
                        $icon = 'delete';
                        $actionTitle = 'Remove environment';
                    } ?>

                    <img class="icon" src="/assets/icons/<?= $icon ?>.svg" title="<?= $actionTitle ?>" />
                </div>

                <div class="flex flex-direction-column row-gap-4">
                    <span>
                        <b>
                            <?php
                            /**
                             *  If task is immediate, display the date and time
                             */
                            if ($item['Type'] == 'immediate') :
                                echo $item['Date'] . ' ' . $item['Time'];
                            endif;

                            /**
                             *  If task is scheduled
                             */
                            if ($item['Type'] == 'scheduled') :
                                /**
                                 *  Case it is a unique scheduled task
                                 */
                                if ($taskRawParams['schedule']['schedule-type'] == 'unique') {
                                    echo 'Scheduled on<br>';
                                    echo $item['Schedule_date'] . ' ' . $item['Schedule_time'] . ':00';
                                }

                                /**
                                 *  Case it is a recurring scheduled task
                                 */
                                if ($taskRawParams['schedule']['schedule-type'] == 'recurrent') {
                                    if ($item['Schedule_frequency'] == 'hourly') {
                                        echo 'Scheduled every hour';
                                    }

                                    if ($item['Schedule_frequency'] == 'daily') {
                                        echo 'Scheduled every day at ' . $item['Schedule_time'] . ':00';
                                    }

                                    if ($item['Schedule_frequency'] == 'weekly') {
                                        echo 'Scheduled every week on ' . str_replace(',', ', ', $item['Schedule_day']) . ' at ' . $item['Schedule_time'] . ':00';
                                    }
                                }
                            endif ?>
                        </b>
                    </span>
                    <span class="lowopacity-cst">
                        <?= $actionTitle ?>
                    </span>
                </div>
  
                <div>
                    <span class="label-white">
                        <?= $myTask->getRepo($item['Id']); ?>
                    </span>

                    <?php
                    /**
                     *  If action is 'env', print environment
                     */
                    if ($taskRawParams['action'] == 'env') {
                        echo \Controllers\Common::envtag($taskRawParams['env']);
                    }

                    /**
                     *  If action is 'removeEnv', print environment
                     */
                    if ($taskRawParams['action'] == 'removeEnv') {
                        echo \Controllers\Common::envtag($taskRawParams['env']);
                    } ?>
                </div>

                <div class="flex align-item-center justify-end">
                    <?php
                    /**
                     *  If task is a scheduled task
                     */
                    if ($item['Type'] == 'scheduled') {
                        /**
                         *  Scheduled task info button
                         */
                        echo '<img class="icon-lowopacity show-scheduled-task-info-btn" src="/assets/icons/info.svg" task-id="' . $item['Id'] . '" title="Scheduled task info" />';

                        /**
                         *  If task is a recurrent task, add the possibility to disable/enable it
                         */
                        if (in_array($item['Schedule_frequency'], ['hourly', 'daily', 'weekly'])) {
                            if ($item['Status'] == 'scheduled') {
                                echo '<img class="icon-lowopacity disable-scheduled-task-btn" src="/assets/icons/disabled.svg" task-id="' . $item['Id'] . '" title="Disable scheduled task" />';
                            }

                            if ($item['Status'] == 'disabled') {
                                echo '<img class="icon-lowopacity enable-scheduled-task-btn" src="/assets/icons/enabled.svg" task-id="' . $item['Id'] . '" title="Enable scheduled task" />';
                            }
                        }

                        /**
                         *  Delete task button
                         */
                        echo '<img class="icon-lowopacity cancel-scheduled-task-btn" src="/assets/icons/delete.svg" task-id="' . $item['Id'] . '" title="Cancel scheduled task" />';

                        /**
                         *  Task status icon
                         */
                        if ($item['Status'] == 'scheduled') {
                            echo '<img class="icon-small" src="/assets/icons/yellowcircle.png" title="Task is scheduled" />';
                        }

                        if ($item['Status'] == 'disabled') {
                            echo '<img class="icon-small" src="/assets/icons/graycircle.png" title="Task execution is disabled" />';
                        }
                    }

                    /**
                     *  Print relaunch button if task has failed
                     */
                    if ($item['Status'] == 'error' or $item['Status'] == 'stopped' and !empty($item['Id']) and IS_ADMIN) {
                        echo '<img class="icon-lowopacity relaunch-task-btn" src="/assets/icons/update.svg" task-id="' . $item['Id'] . '" title="Relaunch this task with the same parameters." />';
                    }

                    if ($item['Status'] == 'running') {
                        echo '<span>running</span> <img src="/assets/images/loading.gif" class="icon" title="running" />';
                    }

                    if ($item['Status'] == 'done') {
                        echo '<img class="icon-small" src="/assets/icons/greencircle.png" title="Task completed" />';
                    }

                    if ($item['Status'] == 'error') {
                        echo '<img class="icon-small" src="/assets/icons/redcircle.png" title="Task has failed" />';
                    }

                    if ($item['Status'] == 'stopped') {
                        echo '<img class="icon-small" src="/assets/icons/redcircle.png" title="Task stopped by the user" />';
                    } ?>
                </div>
            </div>

            <?php
            /**
             *  If task is scheduled, print task info div
             */
            if ($item['Type'] == 'scheduled') : ?>
                <div class="scheduled-task-info plan-info-div margin-bottom-10 hide" task-id="<?= $item['Id'] ?>">
                    <?php
                    if ($taskRawParams['action'] == 'duplicate') : ?>
                        <div>
                            <span>Duplicate to</span>
                            <span><?= $taskRawParams['name'] ?></span>
                        </div>
                        <?php
                    endif;

                    if (!empty($taskRawParams['arch'])) : ?>
                        <div>
                            <span>Architecture</span>
                            <span>
                                <?php
                                foreach ($taskRawParams['arch'] as $architecture) {
                                    echo $architecture . '<br>';
                                } ?>
                            </span>
                        </div>
                        <?php
                    endif;

                    if (!empty($taskRawParams['only-sync-difference'])) : ?>
                        <div>
                            <span>Only sync the difference</span>
                            <span>
                                <?php
                                if ($taskRawParams['only-sync-difference'] == 'true') {
                                    echo '<img src="/assets/icons/greencircle.png" class="icon-small" />Enabled';
                                } else {
                                    echo '<img src="/assets/icons/redcircle.png" class="icon-small" />Disabled';
                                } ?>
                            </span>
                        </div>
                        <?php
                    endif;

                    if (!empty($taskRawParams['gpg-sign'])) : ?>
                        <div>
                            <span>GPG sign</span>
                            <span>
                                <?php
                                if ($taskRawParams['gpg-sign'] == 'true') {
                                    echo '<img src="/assets/icons/greencircle.png" class="icon-small" />Enabled';
                                } else {
                                    echo '<img src="/assets/icons/redcircle.png" class="icon-small" />Disabled';
                                } ?>
                            </span>
                        </div>
                        <?php
                    endif ?>

                    <div>
                        <span>Reminders</span>
                        <span>
                            <?php
                            if (empty($item['Schedule_reminder'])) {
                                echo 'None';
                            } else {
                                foreach (explode(',', $item['Schedule_reminder']) as $reminder) {
                                    echo $reminder . ' day(s) before<br>';
                                }
                            } ?>
                        </span>
                    </div>

                    <div>
                        <span>Notify on error</span>
                        <span>
                            <?php
                            if ($item['Schedule_notify_error'] == 'true') {
                                echo '<img src="/assets/icons/greencircle.png" class="icon-small" />Enabled';
                            } else {
                                echo '<img src="/assets/icons/redcircle.png" class="icon-small" />Disabled';
                            } ?>
                        </span>
                    </div>

                    <div>
                        <span>Notify on success</span>
                        <span>
                            <?php
                            if ($item['Schedule_notify_success'] == 'true') {
                                echo '<img src="/assets/icons/greencircle.png" class="icon-small" />Enabled';
                            } else {
                                echo '<img src="/assets/icons/redcircle.png" class="icon-small" />Disabled';
                            } ?>
                        </span>
                    </div>

                    <div>
                        <span>Contact</span>
                        <span>
                            <?php
                            if (empty($item['Schedule_recipient'])) {
                                echo 'None';
                            } else {
                                echo $item['Schedule_recipient'];
                            } ?>
                        </span>
                    </div>



                </div>
                <?php
            endif;
        endforeach; ?>

        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
