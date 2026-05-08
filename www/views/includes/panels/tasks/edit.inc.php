<?php
use \Controllers\Utils\Generate\Html\Label;

ob_start();

foreach ($tasks as $task) :
    try {
        $params = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        echo '<p>' . $e->getMessage() . '</p>';
        continue;
    }

    // Include task configuration file
    include(ROOT . '/config/tasks/' . $params['action'] . '.php'); ?>

    <div class="task-schedule-form-params" task-id="<?= $task['Id'] ?>">
        <h5 class="margin-top-0">TASK #<?= strtoupper($task['Id']) ?></h5>

        <h6>ACTION</h6>
        <p><?= $taskConfig['description'] ?></p>

        <h6>REPOSITORY</h6>
        <p><?= Label::white($task['Repository']) ?></p>

        <h6 class="required">SCHEDULE TYPE</h6>
        <div class="<?= count($formConfig['allowed-schedule-types']) == 1 ? 'single-' : '' ?>switch-field">
            <?php
            if (in_array('unique', $formConfig['allowed-schedule-types'])) : ?>
                <input type="radio" id="<?= $task['Id'] ?>-task-schedule-type-unique" class="task-param" param-name="schedule-type" name="<?= $task['Id'] ?>-task-schedule-type" value="unique" <?= $params['schedule']['schedule-type'] == 'unique' ? 'checked' : '' ?> task-id="<?= $task['Id'] ?>" />
                <label for="<?= $task['Id'] ?>-task-schedule-type-unique">Unique task</label>
                <?php
            endif;

            if (in_array('recurring', $formConfig['allowed-schedule-types'])) : ?>
                <input type="radio" id="<?= $task['Id'] ?>-task-schedule-type-recurring" class="task-param" param-name="schedule-type" name="<?= $task['Id'] ?>-task-schedule-type" value="recurring" <?= $params['schedule']['schedule-type'] == 'recurring' ? 'checked' : '' ?> task-id="<?= $task['Id'] ?>" />
                <label for="<?= $task['Id'] ?>-task-schedule-type-recurring">Recurrent task</label>
                <?php
            endif; ?>
        </div>

        <?php
        if (in_array('recurring', $formConfig['allowed-schedule-types'])) : ?>
            <div class="task-schedule-recurring-frequency-input <?= $params['schedule']['schedule-type'] == 'recurring' ? '' : 'hide' ?>">
                <h6 class="required">FREQUENCY</h6>
                <select class="task-param" param-name="schedule-frequency" task-id="<?= $task['Id'] ?>">
                    <option value="">Select...</option>
                    <?php
                    foreach (['hourly', 'daily', 'weekly', 'monthly', 'cron'] as $frequency) {
                        $selected = isset($params['schedule']['schedule-frequency']) && $params['schedule']['schedule-frequency'] == $frequency ? 'selected' : '';
                        echo '<option value="' . $frequency . '" ' . $selected . '>' . ucfirst($frequency) . '</option>';
                    } ?>
                </select>
            </div>

            <div class="task-schedule-recurring-day-input <?= $params['schedule']['schedule-type'] == 'recurring' && $params['schedule']['schedule-frequency'] == 'weekly' ? '' : 'hide' ?>">
                <h6 class="required">DAY(S)</h6>
                <select class="task-param" param-name="schedule-day" multiple>
                    <?php
                    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                        $selected = in_array($day, $params['schedule']['schedule-day'] ?? []) ? 'selected' : '';
                        echo '<option value="' . $day . '" ' . $selected . '>' . ucfirst($day) . '</option>';
                    } ?>
                </select>
            </div>

            <div class="task-schedule-recurring-monthly-input <?= $params['schedule']['schedule-type'] == 'recurring' && $params['schedule']['schedule-frequency'] == 'monthly' ? '' : 'hide' ?>">
                <h6 class="required">ON THE</h6>
                <div class="flex justify-space-between column-gap-15">
                    <select class="task-param" param-name="schedule-monthly-day-position">
                        <?php
                        foreach (['first', 'second', 'third', 'last'] as $position) {
                            $selected = isset($params['schedule']['schedule-monthly-day-position']) && $params['schedule']['schedule-monthly-day-position'] == $position ? 'selected' : '';
                            echo '<option value="' . $position . '" ' . $selected . '>' . ucfirst($position) . '</option>';
                        } ?>
                    </select>

                    <select class="task-param" param-name="schedule-monthly-day">
                        <?php
                        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                            $selected = isset($params['schedule']['schedule-monthly-day']) && $params['schedule']['schedule-monthly-day'] == $day ? 'selected' : '';
                            echo '<option value="' . $day . '" ' . $selected . '>' . ucfirst($day) . '</option>';
                        } ?>
                    </select>

                </div>
                <p class="note">of the month</p>
            </div>

            <div class="task-schedule-recurring-cron-input <?= $params['schedule']['schedule-type'] == 'recurring' && $params['schedule']['schedule-frequency'] == 'cron' ? '' : 'hide' ?>">
                <h6 class="required">CRON EXPRESSION</h6>
                <p class="note">Format: minute hour day month weekday.</p>
                <input type="text" class="task-param" param-name="schedule-cron" placeholder="*/15 * * * *" />
            </div>
            <?php
        endif;

        if (in_array('unique', $formConfig['allowed-schedule-types'])) : ?>
            <div class="task-schedule-unique-input <?= $params['schedule']['schedule-type'] == 'unique' ? '' : 'hide' ?>">
                <h6 class="required">DATE</h6>
                <input type="date" class="task-param" param-name="schedule-date" value="<?= $params['schedule']['schedule-date'] ?? '' ?>" />
            </div>
            <?php
        endif; ?>

        <div class="task-schedule-time-input">
            <h6 class="required">TIME</h6>
            <input type="time" class="task-param" param-name="schedule-time" value="<?= $params['schedule']['schedule-time'] ?? '' ?>" />
        </div>

        <h6>NOTIFY ON TASK ERROR</h6>
        <label class="onoff-switch-label">
            <input type="checkbox" class="onoff-switch-input task-param" param-name="schedule-notify-error" value="true" <?= $params['schedule']['schedule-notify-error'] == 'true' ? 'checked' : '' ?> />
            <span class="onoff-switch-slider"></span>
        </label>

        <h6>NOTIFY ON TASK SUCCESS</h6>
        <label class="onoff-switch-label">
            <input type="checkbox" class="onoff-switch-input task-param" param-name="schedule-notify-success" value="true" <?= $params['schedule']['schedule-notify-success'] == 'true' ? 'checked' : '' ?> />
            <span class="onoff-switch-slider"></span>
        </label>

        <h6>SEND A REMINDER</h6>
        <select class="task-param" param-name="schedule-reminder" multiple>
            <?php
            $reminderOptions = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60];
            foreach ($reminderOptions as $option) {
                $selected = in_array($option, $params['schedule']['schedule-reminder'] ?? []) ? 'selected' : '';
                echo '<option value="' . $option . '" ' . $selected . '>' . $option . ' day' . ($option > 1 ? 's' : '') . ' before</option>';
            } ?>
        </select>

        <h6>RECIPIENT(S)</h6>
        <select class="task-param" param-name="schedule-recipient" multiple>
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
    </div>

    <hr class="margin-top-30 margin-bottom-20">
    <?php
endforeach ?>

<br><br>
<button id="edit-scheduled-tasks-btn" type="submit" class="btn-small-green">Save</button>

<script>
$(document).ready(function(){
    myselect2.convert('.task-param[param-name="schedule-day"]', 'Select day(s)');
    myselect2.convert('.task-param[param-name="schedule-reminder"]', 'Select reminder(s)');
    myselect2.convert('.task-param[param-name="schedule-recipient"]', 'Select or add recipient(s)', true);
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'tasks/edit';
$slidePanelTitle = 'EDIT SCHEDULED TASKS';

include(ROOT . '/views/includes/slide-panel.inc.php');
