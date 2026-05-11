<?php
use \Controllers\Utils\Generate\Html\Label; ?>

<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <div class="flex align-item-center justify-space-between margin-bottom-10">
            <h6 class="margin-top-0 margin-bottom-0"><?= strtoupper($taskTableType) ?></h6>

            <?php
            if (in_array($taskTableType, ['scheduled', 'queued'])) : ?>
                <label class="flex align-item-center column-gap-5 pointer opacity-60-cst">
                    <input type="checkbox" class="select-all-checkbox" checkbox-id="<?= $taskTableType . '-task' ?>" title="Select all" />
                    <span class="font-size-12">Select all</span>
                </label>
                <?php
            endif ?>
        </div>

        <div class="flex flex-direction-column row-gap-10">
            <?php
            foreach ($reloadableTableContent as $item) :
                /**
                 *  Retrieve task parameters
                 */
                $taskRawParams = json_decode($item['Raw_params'], true);

                /**
                 *  Determine status accent color
                 */
                if ($item['Status'] == 'done') {
                    $taskAccent = 'task-accent-green';
                } elseif ($item['Status'] == 'error' or $item['Status'] == 'stopped') {
                    $taskAccent = 'task-accent-red';
                } elseif ($item['Status'] == 'running') {
                    $taskAccent = 'task-accent-running';
                } elseif ($item['Status'] == 'queued') {
                    $taskAccent = 'task-accent-yellow';
                } elseif ($item['Status'] == 'scheduled') {
                    $taskAccent = 'task-accent-orange';
                } elseif ($item['Status'] == 'disabled') {
                    $taskAccent = '';
                } else {
                    $taskAccent = '';
                }

                /**
                 *  Determine action title and icon
                 */
                $icon = 'plus';
                $actionTitle = '';

                if ($taskRawParams['action'] == 'create') {
                    $icon = 'plus';
                    if (!isset($taskRawParams['repo-type'])) {
                        $actionTitle = 'New repository';
                    } else {
                        $actionTitle = $taskRawParams['repo-type'] == 'local' ? 'New local repository' : 'New mirror repository';
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
                if ($taskRawParams['action'] == 'rename') {
                    $icon = 'edit';
                    $actionTitle = 'Rename repository';
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
                }

                /**
                 *  Determine click behavior class
                 */
                $actionBtn = in_array($item['Status'], ['scheduled', 'queued', 'disabled']) ? 'task-item-selectable' : 'show-task-btn'; ?>

                <div class="task-item <?= $taskAccent ?> <?= $actionBtn ?> pointer" task-id="<?= $item['Id'] ?>" title="<?= in_array($item['Status'], ['scheduled', 'queued', 'disabled']) ? 'Click to select' : 'View task details' ?>">
                    <div class="flex align-item-center column-gap-20">
                        <?php
                        /**
                         *  Checkbox for scheduled/queued tasks (hidden like snap checkboxes)
                         */
                        if (in_array($item['Status'], ['scheduled', 'queued', 'disabled'])) :
                            if (IS_ADMIN or in_array('delete', USER_PERMISSIONS['tasks']['allowed-actions'])) : ?>
                                <input type="checkbox" class="task-checkbox-input child-checkbox" checkbox-id="<?= $taskTableType ?>-task" checkbox-data-attribute="task-id" task-id="<?= $item['Id'] ?>" title="Select task" />
                                <?php
                            endif;
                        endif ?>

                        <img class="task-item-icon" src="/assets/icons/<?= $icon ?>.svg" title="<?= $actionTitle ?>" />

                        <div class="flex flex-direction-column row-gap-2">
                            <?php
                            /**
                             *  Date and time for immediate tasks
                             */
                            if (!empty($item['Date']) and !empty($item['Time'])) : ?>
                                <span class="task-item-date"><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?> <?= $item['Time'] ?></span>
                                <?php
                            endif;

                            /**
                             *  Schedule info for scheduled tasks
                             */
                            if ($item['Type'] == 'scheduled') : ?>
                                <span class="task-item-schedule mediumopacity-cst flex align-item-center column-gap-8">
                                    <span>
                                    <?php
                                    if ($taskRawParams['schedule']['schedule-type'] == 'unique') {
                                        echo 'Scheduled on ' . DateTime::createFromFormat('Y-m-d', $taskRawParams['schedule']['schedule-date'])->format('d-m-Y') . ' ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                    }
                                    if ($taskRawParams['schedule']['schedule-type'] == 'recurring') {
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly') echo 'Hourly';
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'daily') echo 'Daily';
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly') echo 'Weekly';
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly') echo 'Monthly';
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'cron') echo 'Cron';
                                    } ?>
                                    </span>
                                    <?php if ($item['Status'] == 'disabled') : ?>
                                        <span class="label-white">Disabled</span>
                                    <?php endif ?>
                                </span>
                                <?php
                            endif ?>

                            <span class="task-item-action lowopacity-cst"><?= $actionTitle ?></span>
                        </div>
                    </div>

                    <div class="task-item-repo">
                        <span class="task-item-repo-name"><?= $myTask->getRepo($item['Id']); ?></span>

                        <?php
                        if (in_array($taskRawParams['action'], ['env', 'removeEnv'])) :
                            if (is_string($taskRawParams['env'])) {
                                echo Label::envtag($taskRawParams['env']);
                            }
                            if (is_array($taskRawParams['env'])) {
                                foreach ($taskRawParams['env'] as $env) {
                                    echo Label::envtag($env);
                                }
                            }
                        endif ?>
                    </div>

                    <div class="task-item-status">
                        <?php
                        if (in_array($item['Status'], ['scheduled', 'queued', 'disabled']) and $item['Type'] == 'scheduled') {
                            echo '<img class="icon-lowopacity show-scheduled-task-info-btn" src="/assets/icons/view.svg" task-id="' . $item['Id'] . '" title="Show task details" />';
                        }

                        if (($item['Status'] == 'error' or $item['Status'] == 'stopped') and !empty($item['Id'])) {
                            if (IS_ADMIN or in_array('relaunch', USER_PERMISSIONS['tasks']['allowed-actions'])) {
                                echo '<img class="icon-lowopacity relaunch-task-btn" src="/assets/icons/update.svg" task-id="' . $item['Id'] . '" title="Relaunch this task" />';
                            }
                        }

                        if ($item['Status'] == 'queued') {
                            echo '<img class="icon-np" src="/assets/icons/pending.svg" title="Pending" />';
                        }

                        if ($item['Status'] == 'running') {
                            if (IS_ADMIN or in_array('stop', USER_PERMISSIONS['tasks']['allowed-actions'])) {
                                echo '<span title="Stop task" class="stop-task-btn" task-id="' . $item['Id'] . '"><img src="/assets/icons/stop.svg" class="icon-lowopacity"></span>';
                            }
                            echo '<img src="/assets/icons/loading.svg" class="icon-np" title="Running" />';
                        } ?>
                    </div>
                </div>

                <?php
                /**
                 *  If task is scheduled, print task info div
                 */
                if ($item['Type'] == 'scheduled') : ?>
                    <div class="scheduled-task-info div-generic-blue margin-bottom-10 hide" task-id="<?= $item['Id'] ?>">
                        <div class="grid grid-2">
                            <div>
                                <h6 class="margin-top-0">SCHEDULE TYPE</h6>
                                <?php
                                if ($taskRawParams['schedule']['schedule-type'] == 'unique') : ?>
                                    <p>Single execution</p>
                                    <?php
                                endif;
                                if ($taskRawParams['schedule']['schedule-type'] == 'recurring') : ?>
                                    <p>Recurring execution</p>
                                    <?php
                                endif ?>
                            </div>

                            <div>
                                <?php
                                if ($taskRawParams['schedule']['schedule-type'] == 'unique') : ?>
                                    <h6 class="margin-top-0">SCHEDULE DATE</h6>
                                    <p><?= DateTime::createFromFormat('Y-m-d', $taskRawParams['schedule']['schedule-date'])->format('d-m-Y') . ' ' . $taskRawParams['schedule']['schedule-time'] . ':00' ?></p>
                                    <?php
                                endif;
                                if ($taskRawParams['schedule']['schedule-type'] == 'recurring') : ?>
                                    <h6 class="margin-top-0">SCHEDULE FREQUENCY</h6>
                                    <p>
                                        <?php
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly') echo 'Every hour';
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'daily') echo 'Every day at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly') echo 'Every week on ' . implode(', ', $taskRawParams['schedule']['schedule-day']) . ' at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly') echo 'Every ' . $taskRawParams['schedule']['schedule-monthly-day-position'] . ' ' . $taskRawParams['schedule']['schedule-monthly-day'] . ' of the month at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'cron') echo 'Cron: ' . htmlspecialchars($taskRawParams['schedule']['schedule-cron'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
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

                        if ($taskRawParams['action'] == 'rename') : ?>
                            <h6>RENAME TO</h6>
                            <p><?= $taskRawParams['name'] ?></p>
                            <?php
                        endif ?>

                        <div class="grid grid-2">
                            <?php
                            if (!empty($taskRawParams['arch'])) : ?>
                                <div>
                                    <h6>ARCHITECTURE</h6>
                                    <div class="flex align-item-center row-gap-5 column-gap-5">
                                        <?php
                                        foreach ($taskRawParams['arch'] as $architecture) {
                                            echo '<p>' . Label::white($architecture) . '</p>';
                                        } ?>
                                    </div>
                                </div>
                                <?php
                            endif;

                            if (!empty($taskRawParams['env'])) : ?>
                                <div>
                                    <h6>ENVIRONMENT</h6>
                                    <div class="flex align-item-center row-gap-5 column-gap-5">
                                        <?php
                                        foreach ($taskRawParams['env'] as $env) {
                                            echo '<p>' . Label::envtag($env) . '</p>';
                                        } ?>
                                    </div>
                                </div>
                                <?php
                            endif ?>
                        </div>

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
                                            echo '<img src="/assets/icons/error.svg" class="icon" />';
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
                                            echo '<img src="/assets/icons/error.svg" class="icon" />';
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
                                        echo '<img src="/assets/icons/error.svg" class="icon" />';
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
                                        echo '<img src="/assets/icons/error.svg" class="icon" />';
                                        echo '<span>Disabled</span>';
                                    } ?>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-2">
                            <div>
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
                            </div>

                            <div>
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
                        </div>
                    </div>
                    <?php
                endif;
            endforeach; ?>
        </div>

        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <br><br>

        <?php
    endif;

    unset($checkboxId, $tableClass, $headerColor, $actionBtn, $taskRawParams, $item); ?>
</div>
