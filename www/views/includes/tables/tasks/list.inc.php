<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        if (!empty($taskTableTitle)) {
            echo '<h6 class="margin-top-0 margin-bottom-5">' . $taskTableTitle . '</h6>';
        }

        foreach ($reloadableTableContent as $item) :
            $headerColor = '';

            /**
             *  Retrieve task parameters
             */
            $taskRawParams = json_decode($item['Raw_params'], true);

            /**
             *  If the current task item was made in a scheduled task, we display the scheduled task header
             */
            if ($item['Status'] == 'scheduled' or $item['Status'] == 'queued') {
                $headerColor = 'header-blue-min';
            }

            $tableClass = 'table-container grid-40p-45p-10p column-gap-10 justify-space-between pointer show-task-btn ' . $headerColor; ?>

            <div class="<?= $tableClass ?>" task-id="<?= $item['Id'] ?>" title="View task log">
                <div class="flex align-item-center column-gap-15">
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
                        <?php
                        /**
                         *  If task is immediate, display the date and time
                         */
                        if (!empty($item['Date']) and !empty($item['Time'])) :
                            echo '<span><b>' . DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') . ' ' . $item['Time'] . '</b></span>';
                        endif;

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

                <div class="flex align-item-center justify-end column-gap-10 row-gap-10 flex-wrap">
                    <?php
                    /**
                     *  Delete task button, only for scheduled and queued tasks
                     */
                    if ($item['Status'] == 'scheduled' or $item['Status'] == 'queued') {
                        echo '<img class="icon-lowopacity cancel-task-btn" src="/assets/icons/delete.svg" task-id="' . $item['Id'] . '" title="Cancel and delete scheduled task" />';
                    }

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
                         *  Task status icon
                         */
                        if ($item['Status'] == 'disabled') {
                            echo '<img class="icon-np" src="/assets/icons/warning.svg" title="Task execution is disabled" />';
                        }
                    }

                    /**
                     *  Print relaunch button if task has failed
                     */
                    if (($item['Status'] == 'error' or $item['Status'] == 'stopped') and !empty($item['Id']) and IS_ADMIN) {
                        echo '<img class="icon-lowopacity relaunch-task-btn" src="/assets/icons/update.svg" task-id="' . $item['Id'] . '" title="Relaunch this task with the same parameters." />';
                    }

                    if ($item['Status'] == 'scheduled' or $item['Status'] == 'queued') {
                        echo '<img class="icon-np" src="/assets/icons/pending.svg" title="Task is pending" />';
                    }

                    if ($item['Status'] == 'running') {
                        echo '<span title="Stop task" class="stop-task-btn" task-id="' . $item['Id'] . '"><img src="/assets/icons/delete.svg" class="icon-lowopacity"></span>';
                        echo '<img src="/assets/icons/loading.svg" class="icon-np" title="Task running" />';
                    }

                    if ($item['Status'] == 'done') {
                        echo '<img class="icon-np" src="/assets/icons/check.svg" title="Task completed" />';
                    }

                    if ($item['Status'] == 'error') {
                        echo '<img class="icon-np" src="/assets/icons/error.svg" title="Task has failed" />';
                    }

                    if ($item['Status'] == 'stopped') {
                        echo '<img class="icon-np" src="/assets/icons/warning-red.svg" title="Task stopped by the user" />';
                    } ?>
                </div>
            </div>

            <?php
            /**
             *  If task is scheduled, print task info div
             */
            if ($item['Type'] == 'scheduled') : ?>
                <div class="scheduled-task-info detailsDiv margin-bottom-10 hide" task-id="<?= $item['Id'] ?>">
                    <div class="grid grid-2">
                        <div>
                            <h6 class="margin-top-0">SCHEDULE TYPE</h6>
                            <?php
                            // Case it is a unique scheduled task
                            if ($taskRawParams['schedule']['schedule-type'] == 'unique') : ?>
                                <p>Single execution</p>
                                <?php
                            endif;

                            // Case it is a recurring scheduled task
                            if ($taskRawParams['schedule']['schedule-type'] == 'recurring') : ?>
                                <p>Recurring execution</p>
                                <?php
                            endif ?>
                        </div>

                        <div>
                            <?php
                            // Case it is a unique scheduled task
                            if ($taskRawParams['schedule']['schedule-type'] == 'unique') : ?>
                                <h6 class="margin-top-0">SCHEDULE DATE</h6>
                                <p><?=  DateTime::createFromFormat('Y-m-d', $taskRawParams['schedule']['schedule-date'])->format('d-m-Y') . ' ' . $taskRawParams['schedule']['schedule-time'] . ':00' ?></p>
                                <?php
                            endif;

                            // Case it is a recurring scheduled task
                            if ($taskRawParams['schedule']['schedule-type'] == 'recurring') : ?>
                                <h6 class="margin-top-0">SCHEDULE FREQUENCY</h6>
                                <p>
                                    <?php
                                    if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly') {
                                        echo 'Every hour';
                                    }

                                    if ($taskRawParams['schedule']['schedule-frequency'] == 'daily') {
                                        echo 'Every day at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                    }

                                    if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly') {
                                        echo 'Every week on ' . implode(', ', $taskRawParams['schedule']['schedule-day']) . ' at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                    }

                                    if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly') {
                                        echo 'Every ' . $taskRawParams['schedule']['schedule-monthly-day-position'] . ' ' . $taskRawParams['schedule']['schedule-monthly-day'] . ' of each month at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                    } ?>
                                </p>
                                <?php
                            endif ?>
                        </div>
                    </div>

                    <?php
                    if ($taskRawParams['action'] == 'duplicate') : ?>
                        <h6>DUPLICATE TO</h6>
                        <p><?= $taskRawParams['name'] ?></p>
                        <?php
                    endif;

                    if (!empty($taskRawParams['arch'])) : ?>
                        <h6>ARCHITECTURE</h6>
                        <div class="flex row-gap-5 column-gap-5">
                            <?php
                            foreach ($taskRawParams['arch'] as $architecture) {
                                echo '<span class="label-black">' . $architecture . '</span>';
                            } ?>
                        </div>
                        <?php
                    endif ?>

                    <div class="grid grid-2">
                        <?php
                        if (!empty($taskRawParams['gpg-check'])) : ?>
                            <div>
                                <h6>CHECK GPG SIGNATURES</h6>
                                <div class="flex align-item-center column-gap-5">
                                    <?php
                                    if ($taskRawParams['gpg-check'] == 'true') {
                                        echo '<img src="/assets/icons/check.svg" class="icon" />';
                                        echo '<span>Enabled</span>';
                                    } else {
                                        echo '<img src="/assets/icons/warning-red.svg" class="icon" />';
                                        echo '<span>Disabled</span>';
                                    } ?>
                                </div>
                            </div>
                            <?php
                        endif;

                        if (!empty($taskRawParams['gpg-sign'])) : ?>
                            <div>
                                <h6>SIGN WITH GPG</h6>
                                <div class="flex align-item-center column-gap-5">
                                    <?php
                                    if ($taskRawParams['gpg-sign'] == 'true') {
                                        echo '<img src="/assets/icons/check.svg" class="icon" />';
                                        echo '<span>Enabled</span>';
                                    } else {
                                        echo '<img src="/assets/icons/warning-red.svg" class="icon" />';
                                        echo '<span>Disabled</span>';
                                    } ?>
                                </div>
                            </div>
                            <?php
                        endif ?>
                    </div>

                    <div class="grid grid-2">
                        <div>
                            <h6>NOTIFY ON TASK ERROR</h6>
                            <div class="flex align-item-center column-gap-5">
                                <?php
                                if ($taskRawParams['schedule']['schedule-notify-error'] == 'true') {
                                    echo '<img src="/assets/icons/check.svg" class="icon" />';
                                    echo '<span>Enabled</span>';
                                } else {
                                    echo '<img src="/assets/icons/warning-red.svg" class="icon" />';
                                    echo '<span>Disabled</span>';
                                } ?>
                            </div>
                        </div>

                        <div>
                            <h6>NOTIFY ON TASK SUCCESS</h6>
                            <div class="flex align-item-center column-gap-5">
                                <?php
                                if ($taskRawParams['schedule']['schedule-notify-success'] == 'true') {
                                    echo '<img src="/assets/icons/check.svg" class="icon" />';
                                    echo '<span>Enabled</span>';
                                } else {
                                    echo '<img src="/assets/icons/warning-red.svg" class="icon" />';
                                    echo '<span>Disabled</span>';
                                } ?>
                            </div>
                        </div>
                    </div>

                    <h6>SEND A REMINDER</h6>
                    <p>
                        <?php
                        if (empty($taskRawParams['schedule']['schedule-reminder'])) {
                            echo 'None';
                        } else {
                            foreach ($taskRawParams['schedule']['schedule-reminder'] as $reminder) {
                                if ($reminder == 1) {
                                    echo '1 day before<br>';
                                } else {
                                    echo $reminder . ' days before<br>';
                                }
                            }
                        } ?>
                    </p>

                    <h6>CONTACT</h6>
                    <p>
                        <?php
                        if (empty($taskRawParams['schedule']['schedule-recipient'])) {
                            echo 'None';
                        } else {
                            foreach ($taskRawParams['schedule']['schedule-recipient'] as $recipient) {
                                echo $recipient . '<br>';
                            }
                        } ?>
                    </p>
                </div>
                <?php
            endif;
        endforeach; ?>

        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <br><br>

        <?php
    endif ?>
</div>
