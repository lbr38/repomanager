<?php
use \Controllers\User\Permission\Task as TaskPermission;
use \Controllers\Layout\Table\Render as TableRender;
use \Controllers\Utils\Generate\Html\Label;
use \Controllers\Task\Task; ?>

<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <div class="flex align-item-center column-gap-20 margin-bottom-10">
            <h6 class="margin-top-0 margin-bottom-0"><?= strtoupper($taskTableType) ?></h6>

            <?php
            // Print Select all checkbox for scheduled and queued tasks if there are more than 1 task
            if (in_array($taskTableType, ['scheduled', 'queued']) and (count($reloadableTableContent) > 1)) : ?>
                <div class="select-all-btn task-select-all-btns btn-fit-tr align-item-center column-gap-8 pointer" checkbox-id="<?= $taskTableType . '-task' ?>" title="Select all">
                    <span>Select all</span>
                    <input type="checkbox" class="select-all-checkbox" checkbox-id="<?= $taskTableType . '-task' ?>" title="Select all" aria-hidden="true" tabindex="-1" />
                </div>
                <?php
            endif ?>
        </div>

        <div class="flex flex-direction-column row-gap-10">
            <?php
            // Parent tasks have no status of their own, their real status is a summary of their sub-tasks statuses
            $subTasksSummaries = $taskListingController->getSubTasksSummaries(array_column($reloadableTableContent, 'Id'));

            foreach ($reloadableTableContent as $item) :
                $taskAccent = '';

                // Retrieve task parameters
                try {
                    $taskRawParams = json_decode($item['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    echo '<p class="note">Error decoding task #' . $item['Id'] . ' parameters: ' . $e->getMessage() . '</p>';
                    continue;
                }

                $subTasksSummary = $subTasksSummaries[$item['Id']] ?? [];
                $itemStatus = !empty($subTasksSummary) ? $subTasksSummary['status'] : $item['Status'];

                /**
                 *  Sub-tasks of a parent task only exist in database once the task has been executed,
                 *  until then they are only described by the parent task parameters
                 */
                $subTasks = $taskListingController->getByParentId($item['Id']);
                $hasSubTasks = (!empty($subTasks) or !empty($taskRawParams['tasks']));

                // Determine status accent color
                if ($itemStatus == 'done') {
                    $taskAccent = 'accent-green';
                } elseif ($itemStatus == 'error' or $itemStatus == 'stopped') {
                    $taskAccent = 'accent-red';
                } elseif ($itemStatus == 'running') {
                    $taskAccent = 'accent-cyan';
                } elseif ($itemStatus == 'queued') {
                    $taskAccent = 'accent-yellow';
                } elseif ($itemStatus == 'scheduled') {
                    $taskAccent = 'accent-orange';
                }

                // Determine action title and icon
                $actionTitle = Task::generateLiteralAction($item);
                $title = $actionTitle['title'];
                $icon  = $actionTitle['icon'];

                // Determine click behavior class
                $actionBtn = in_array($item['Status'], ['scheduled', 'queued', 'disabled']) ? 'task-item-selectable' : ''; ?>

                <div class="task-item <?= $taskAccent ?> <?= $actionBtn ?> pointer" task-id="<?= $item['Id'] ?>" title="<?= in_array($item['Status'], ['scheduled', 'queued', 'disabled']) ? 'Click to select' : 'View task details' ?>">
                    <a <?= in_array($item['Status'], ['scheduled', 'queued', 'disabled']) ? '' : 'href="/task/' . $item['Id'] . '"'; ?>>
                        <div class="flex align-item-center column-gap-20">
                            <?php
                            // Checkbox for scheduled/queued tasks (hidden like snap checkboxes)
                            if (in_array($item['Status'], ['scheduled', 'queued', 'disabled'])) :
                                if (TaskPermission::allowedAction('delete')) : ?>
                                    <input type="checkbox" class="task-checkbox-input child-checkbox" checkbox-id="<?= $taskTableType ?>-task" checkbox-data-attribute="task-id" task-id="<?= $item['Id'] ?>" title="Select task" />
                                    <?php
                                endif;
                            endif ?>

                            <img class="icon-np <?= $itemStatus != 'running' ? 'icon-lowopacity' : '' ?>" src="/assets/icons/<?= $icon ?>" title="<?= $title ?>" />

                            <div class="flex flex-direction-column row-gap-2">
                                <?php
                                // Date and time for immediate tasks
                                if (!empty($item['Date']) and !empty($item['Time'])) : ?>
                                    <p class="task-item-date"><?= $item['Status'] == 'scheduled' ? 'Last execution: ' : '' ?><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?> <?= $item['Time'] ?></p>
                                    <?php
                                endif;

                                if ($item['Status'] == 'queued') : ?>
                                    <div class="flex align-item-center column-gap-8">
                                        <span class="mediumopacity-cst">Task #<?= $item['Id'] ?></span>
                                        <span class="label-yellow">Pending</span>
                                    </div>
                                    <?php
                                endif;

                                // Schedule info for scheduled tasks
                                if ($item['Type'] == 'scheduled') : ?>
                                    <div class="task-item-schedule flex align-item-center column-gap-8">
                                        <span class="mediumopacity-cst">
                                            <?php
                                            if ($taskRawParams['schedule']['schedule-type'] == 'unique') {
                                                echo 'Scheduled on ' . DateTime::createFromFormat('Y-m-d', $taskRawParams['schedule']['schedule-date'])->format('d-m-Y') . ' ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                            }
                                            if ($taskRawParams['schedule']['schedule-type'] == 'recurring') {
                                                $scheduleTime = ' at ' . htmlspecialchars($taskRawParams['schedule']['schedule-time'] ?? '', ENT_QUOTES, 'UTF-8') . ':00';

                                                if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly') {
                                                    echo 'Hourly';
                                                }
                                                if ($taskRawParams['schedule']['schedule-frequency'] == 'daily') {
                                                    echo 'Daily' . $scheduleTime;
                                                }
                                                if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly') {
                                                    echo 'Weekly' . $scheduleTime;
                                                }
                                                if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly') {
                                                    echo 'Monthly' . $scheduleTime;
                                                }
                                                if ($taskRawParams['schedule']['schedule-frequency'] == 'cron') {
                                                    echo 'Cron: ' . htmlspecialchars($taskRawParams['schedule']['schedule-cron'] ?? '', ENT_QUOTES, 'UTF-8');
                                                }
                                            } ?>
                                        </span>

                                        <?php
                                        // If task date schedule is in the past, print a label
                                        if (!empty($taskRawParams['schedule']['schedule-date']) and !empty($taskRawParams['schedule']['schedule-time']) and $item['Status'] == 'scheduled') {
                                            $taskDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $taskRawParams['schedule']['schedule-date'] . ' ' . $taskRawParams['schedule']['schedule-time'] . ':00');
                                            $now = new DateTime();

                                            if ($taskDateTime < $now) {
                                                echo '<span class="label-red" title="This task is scheduled in the past and will not be executed">Past schedule</span>';
                                            }
                                        }

                                        // If the task is scheduled, queued or disabled, print a button to view its parameters
                                        if (in_array($item['Status'], ['scheduled', 'queued', 'disabled']) and $item['Type'] == 'scheduled') {
                                            echo '<img class="icon-lowopacity show-scheduled-task-info-btn" src="/assets/icons/view.svg" task-id="' . $item['Id'] . '" title="Show task details" />';
                                        }

                                        // If the task is disabled, print a label
                                        if ($item['Status'] == 'disabled') : ?>
                                            <span class="label-white">Disabled</span>
                                            <?php
                                        endif; ?>
                                    </div>
                                    <?php
                                endif ?>

                                <span class="task-item-action mediumopacity-cst"><?= $title ?></span>
                            </div>
                        </div>
                    </a>

                    <div class="task-item-repo">
                        <?php
                        // If the task has sub-tasks, print a summary of their results
                        if (!empty($subTasksSummary)) {
                            if ($subTasksSummary['status'] == 'error') {
                                $subTasksLabel = 'label-red';
                            } elseif ($subTasksSummary['status'] == 'running') {
                                $subTasksLabel = 'label-yellow';
                            } else {
                                $subTasksLabel = 'label-green';
                            }

                            echo '<a href="/task/' . $item['Id'] . '"><span class="' . $subTasksLabel . '" title="' . $subTasksSummary['success'] . ' sub-task(s) succeeded out of ' . $subTasksSummary['total'] . ', click to view">' . $subTasksSummary['success'] . '/' . $subTasksSummary['total'] . '</span></a>';
                        }

                        // When the task has sub-tasks, the repositories badge acts as the expand/collapse button
                        if ($hasSubTasks) {
                            echo '<span class="label-white toggle-subtasks-btn" task-id="' . $item['Id'] . '" title="Expand sub-tasks">' . $taskController->getRepo($item['Id']) . '</span>';
                        } else {
                            echo '<span class="label-white nopointer" title="Target repository(ies)">' . $taskController->getRepo($item['Id']) . '</span>';
                        }

                        if (in_array($taskRawParams['action'], ['env', 'removeEnv'])) {
                            if (is_string($taskRawParams['env'])) {
                                echo Label::envtag($taskRawParams['env']);
                            }

                            if (is_array($taskRawParams['env'])) {
                                foreach ($taskRawParams['env'] as $env) {
                                    echo Label::envtag($env);
                                }
                            }
                        } ?>
                    </div>

                    <div class="task-item-status">
                        <?php
                        // Show the relaunch button only for tasks that have no sub-tasks
                        if (!$hasSubTasks and ($item['Status'] == 'error' or $item['Status'] == 'stopped') and !empty($item['Id'])) {
                            if (TaskPermission::allowedAction('relaunch')) {
                                echo '<img class="icon-lowopacity relaunch-task-btn" src="/assets/icons/update.svg" task-id="' . $item['Id'] . '" title="Relaunch this task" />';
                            }
                        }

                        if ($item['Status'] == 'queued') {
                            echo '<img class="icon-np" src="/assets/icons/pending.svg" title="Pending" />';
                        }

                        if ($item['Status'] == 'running') {
                            if (TaskPermission::allowedAction('stop')) {
                                echo '<span title="Stop task" class="stop-task-btn" task-id="' . $item['Id'] . '"><img src="/assets/icons/stop.svg" class="icon-lowopacity"></span>';
                            }
                        } ?>
                    </div>
                </div>

                <?php
                // Print the sub-tasks of a parent task, so that all the repositories it targets are grouped under it
                if (!empty($subTasks)) : ?>
                    <div class="task-item-children" task-id="<?= $item['Id'] ?>">
                        <?php
                        foreach ($subTasks as $subTask) :
                            $subTaskAccent = 'yellow';

                            if ($subTask['Status'] == 'done') {
                                $subTaskAccent = 'green';
                            } elseif ($subTask['Status'] == 'error' or $subTask['Status'] == 'stopped') {
                                $subTaskAccent = 'red';
                            } elseif ($subTask['Status'] == 'running') {
                                $subTaskAccent = 'cyan';
                            } ?>

                            <div class="task-item-child div-generic-blue accent-<?= $subTaskAccent ?>">
                                <a class="flex align-item-center column-gap-10" href="/task/<?= $subTask['Id'] ?>" title="View sub-task #<?= $subTask['Id'] ?> details">
                                    <?php
                                    if ($subTask['Status'] == 'running') {
                                        echo '<img class="icon-np" src="/assets/icons/loading.svg" title="Running" />';
                                    } ?>

                                    <span class="label-white"><?= $taskController->getRepo($subTask['Id']) ?></span>
                                </a>

                                <div class="task-item-status">
                                    <?php
                                    if ($subTask['Status'] == 'running') {
                                        if (TaskPermission::allowedAction('stop')) {
                                            echo '<span title="Stop task" class="stop-task-btn" task-id="' . $subTask['Id'] . '"><img src="/assets/icons/stop.svg" class="icon-lowopacity"></span>';
                                        }
                                    }

                                    if ($subTask['Status'] == 'error' or $subTask['Status'] == 'stopped') {
                                        if (TaskPermission::allowedAction('relaunch')) {
                                            echo '<img class="icon-lowopacity relaunch-task-btn" src="/assets/icons/update.svg" task-id="' . $subTask['Id'] . '" title="Relaunch this task" />';
                                        }
                                    } ?>
                                </div>
                            </div>
                            <?php
                        endforeach ?>
                    </div>
                    <?php
                elseif (!empty($taskRawParams['tasks'])) : ?>
                    <div class="task-item-children" task-id="<?= $item['Id'] ?>">
                        <?php
                        foreach ($taskRawParams['tasks'] as $subTaskParams) : ?>
                            <div class="task-item-child div-generic-blue accent-orange">
                                <span class="label-white"><?= $taskController->getRepoFromParams($subTaskParams) ?></span>
                            </div>
                            <?php
                        endforeach ?>
                    </div>
                    <?php
                endif ?>

                <?php
                // If task is scheduled, print task info div
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
                                            echo 'Every ' . $taskRawParams['schedule']['schedule-monthly-day-position'] . ' ' . $taskRawParams['schedule']['schedule-monthly-day'] . ' of the month at ' . $taskRawParams['schedule']['schedule-time'] . ':00';
                                        }
                                        if ($taskRawParams['schedule']['schedule-frequency'] == 'cron') {
                                            echo 'Cron: ' . htmlspecialchars($taskRawParams['schedule']['schedule-cron'] ?? '', ENT_QUOTES, 'UTF-8');
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
            <?php TableRender::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <br><br>

        <?php
    endif;

    unset($checkboxId, $tableClass, $headerColor, $actionBtn, $taskRawParams, $item); ?>
</div>
