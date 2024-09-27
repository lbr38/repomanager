<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) :
            $tableTitle  = '';
            $headerColor = '';

            /**
             *  Retrieve task parameters
             */
            $taskRawParams = json_decode($item['Raw_params'], true);

            /**
             *  If the current task item was made in a scheduled task, we display the scheduled task header
             */
            if ($item['Type'] == 'scheduled' and $item['Status'] == 'scheduled') {
                $headerColor = 'header-light-blue';
            }

            $tableClass = 'table-container ' . $headerColor;

            /**
             *  If task has a logfile, we add the pointer class to the table container
             */
            if (!empty($item['Logfile'])) {
                $tableClass .= ' pointer task-logfile-btn';
                $tableTitle = 'View task log';
            } ?>

            <div class="<?= $tableClass ?>" logfile="<?= $item['Logfile'] ?>" title="<?= $tableTitle ?>">
                <div>
                    <?php
                    if ($taskRawParams['action'] == 'create') {
                        $icon = 'plus';

                        /**
                         *  To keep compatibility with old tasks (old operations table)
                         *  TODO: delete this in 1year
                         */
                        if (!isset($taskRawParams['repo-type'])) {
                            $actionTitle = 'New repository';
                        } else {
                            if ($taskRawParams['repo-type'] == 'local') {
                                $actionTitle = 'New local repository';
                            }
                            if ($taskRawParams['repo-type'] == 'mirror') {
                                $actionTitle = 'New mirror repository';
                            }
                        }
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
                            if (!empty($item['Date']) and !empty($item['Time'])) :
                                echo DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') . ' ' . $item['Time'];
                            endif ?>
                        </b>
                    </span>

                    <?php
                    /**
                     *  If task is scheduled
                     */
                    if ($item['Type'] == 'scheduled') :
                        if (!empty($item['Date']) and !empty($item['Time'])) {
                            $class = 'margin-top-5';
                        } else {
                            $class = '';
                        } ?>

                        <span class="<?= $class ?>">
                            <?php
                            /**
                             *  Case it is a unique scheduled task
                             */
                            if ($taskRawParams['schedule']['schedule-type'] == 'unique') {
                                echo 'Scheduled on<br>';
                                echo DateTime::createFromFormat('Y-m-d', $taskRawParams['schedule']['schedule-date'])->format('d-m-Y') . ' ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                            }

                            /**
                             *  Case it is a recurring scheduled task
                             */
                            if ($taskRawParams['schedule']['schedule-type'] == 'recurring') {
                                if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly') {
                                    echo 'Hourly scheduled task';
                                }

                                if ($taskRawParams['schedule']['schedule-frequency'] == 'daily') {
                                    echo 'Daily scheduled task';
                                }

                                if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly') {
                                    echo 'Weekly scheduled task';
                                }

                                if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly') {
                                    echo 'Monthly scheduled task';
                                }
                            } ?>
                        </span>
                        <?php
                    endif ?>

                    <span class="lowopacity-cst">
                        <?= $actionTitle ?>
                    </span>
                </div>
  
                <div class="flex row-gap-5 column-gap-5 flex-wrap">
                    <span class="label-white">
                        <?= $myTask->getRepo($item['Id']); ?>
                    </span>

                    <?php
                    /**
                     *  If action is 'env' or 'removeEnv', print environment
                     */
                    if ($taskRawParams['action'] == 'env' || $taskRawParams['action'] == 'removeEnv') {
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
                         *  If task is a recurring task, add the possibility to disable/enable it
                         */
                        if ($taskRawParams['schedule']['schedule-type'] == 'recurring') {
                            if (in_array($taskRawParams['schedule']['schedule-frequency'], ['hourly', 'daily', 'weekly', 'monthly'])) {
                                if ($item['Status'] == 'scheduled') {
                                    echo '<img class="icon-lowopacity disable-scheduled-task-btn" src="/assets/icons/disabled.svg" task-id="' . $item['Id'] . '" title="Disable scheduled task" />';
                                }

                                if ($item['Status'] == 'disabled') {
                                    echo '<img class="icon-lowopacity enable-scheduled-task-btn" src="/assets/icons/enabled.svg" task-id="' . $item['Id'] . '" title="Enable scheduled task" />';
                                }
                            }
                        }

                        /**
                         *  Delete task button
                         */
                        if ($item['Status'] == 'scheduled') {
                            echo '<img class="icon-lowopacity cancel-scheduled-task-btn" src="/assets/icons/delete.svg" task-id="' . $item['Id'] . '" title="Cancel scheduled task" />';
                        }

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
                        echo '<span title="Stop task" class="stop-task-btn" pid="' . $item['Pid'] . '"><img src="/assets/icons/delete.svg" class="icon-lowopacity"></span>';
                        echo '<img src="/assets/icons/loading.svg" class="icon" title="Task running" />';
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
                <div class="scheduled-task-info detailsDiv margin-bottom-10 hide" task-id="<?= $item['Id'] ?>">
                    <div class="grid grid-2 row-gap-15 align-item-center margin-top-10 margin-right-10 margin-bottom-10 margin-left-10">
                        <?php
                        /**
                         *  Case it is a unique scheduled task
                         */
                        if ($taskRawParams['schedule']['schedule-type'] == 'unique') : ?>
                            <span>Schedule type</span>
                            <span>Single execution</span>

                            <span>Schedule date</span>
                            <span><?=  DateTime::createFromFormat('Y-m-d', $taskRawParams['schedule']['schedule-date'])->format('d-m-Y') . ' ' . $taskRawParams['schedule']['schedule-time'] . ':00' ?></span>
                            <?php
                        endif;

                        /**
                         *  Case it is a recurring scheduled task
                         */
                        if ($taskRawParams['schedule']['schedule-type'] == 'recurring') : ?>
                            <span>Schedule type</span>
                            <span>Recurring execution</span>

                            <span>Schedule frequency</span>
                            <span>
                                <?php
                                if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly') {
                                    echo 'Hourly';
                                }

                                if ($taskRawParams['schedule']['schedule-frequency'] == 'daily') {
                                    echo 'Daily<br>Every day at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                }

                                if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly') {
                                    echo 'Weekly<br>Every week on ' . implode(', ', $taskRawParams['schedule']['schedule-day']) . ' at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                }

                                if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly') {
                                    echo 'Monthly<br>Every ' . $taskRawParams['schedule']['schedule-monthly-day-position'] . ' ' . $taskRawParams['schedule']['schedule-monthly-day'] . ' of each month at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                } ?>
                            </span>
                            <?php
                        endif;

                        if ($taskRawParams['action'] == 'duplicate') : ?>
                            <span>Duplicate to</span>
                            <span><?= $taskRawParams['name'] ?></span>
                            <?php
                        endif;

                        if (!empty($taskRawParams['arch'])) : ?>
                            <span>Architecture</span>
                            <p class="flex row-gap-5 column-gap-5">
                                <?php
                                foreach ($taskRawParams['arch'] as $architecture) {
                                    echo '<i class="label-black">' . $architecture . '</i>';
                                } ?>
                            </p>
                            <?php
                        endif;

                        if (!empty($taskRawParams['gpg-check'])) : ?>
                            <span>Check GPG signature</span>
                            <span>
                                <?php
                                if ($taskRawParams['gpg-check'] == 'true') {
                                    echo '<img src="/assets/icons/greencircle.png" class="icon-small" />Enabled';
                                } else {
                                    echo '<img src="/assets/icons/redcircle.png" class="icon-small" />Disabled';
                                } ?>
                            </span>
                            <?php
                        endif;

                        if (!empty($taskRawParams['gpg-sign'])) : ?>
                            <span>Sign with GPG</span>
                            <span>
                                <?php
                                if ($taskRawParams['gpg-sign'] == 'true') {
                                    echo '<img src="/assets/icons/greencircle.png" class="icon-small" />Enabled';
                                } else {
                                    echo '<img src="/assets/icons/redcircle.png" class="icon-small" />Disabled';
                                } ?>
                            </span>
                            <?php
                        endif ?>

                        <span>Reminders</span>
                        <span>
                            <?php
                            if (empty($taskRawParams['schedule']['schedule-reminder'])) {
                                echo 'None';
                            } else {
                                foreach ($taskRawParams['schedule']['schedule-reminder'] as $reminder) {
                                    echo $reminder . ' day(s) before<br>';
                                }
                            } ?>
                        </span>

                        <span>Notify on error</span>
                        <span>
                            <?php
                            if ($taskRawParams['schedule']['schedule-notify-error'] == 'true') {
                                echo '<img src="/assets/icons/greencircle.png" class="icon-small" />Enabled';
                            } else {
                                echo '<img src="/assets/icons/redcircle.png" class="icon-small" />Disabled';
                            } ?>
                        </span>

                        <span>Notify on success</span>
                        <span>
                            <?php
                            if ($taskRawParams['schedule']['schedule-notify-success'] == 'true') {
                                echo '<img src="/assets/icons/greencircle.png" class="icon-small" />Enabled';
                            } else {
                                echo '<img src="/assets/icons/redcircle.png" class="icon-small" />Disabled';
                            } ?>
                        </span>

                        <span>Contact</span>
                        <span>
                            <?php
                            if (empty($taskRawParams['schedule']['schedule-recipient'])) {
                                echo 'None';
                            } else {
                                foreach ($taskRawParams['schedule']['schedule-recipient'] as $recipient) {
                                    echo $recipient . '<br>';
                                }
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
