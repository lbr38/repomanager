<?php
use Controllers\Utils\Random; ?>

<div class="task-schedule-form-params" action="<?= $scheduleForm['action'] ?>">
    <h6>TASK SCHEDULING</h6>
    <p class="note">Don't want to execute the task immediately? Schedule it!</p>

    <h6>SCHEDULE IT</h6>
    <label class="onoff-switch-label">
        <input type="checkbox" class="task-schedule-btn onoff-switch-input task-param" param-name="scheduled" value="true" />
        <span class="onoff-switch-slider"></span>
    </label>

    <!-- Scheduling params -->
    <div class="task-schedule-params hide">
        <h6 class="required">SCHEDULE TYPE</h6>
        <?php
        // Generate a random string to make radio Id unique and avoid conflicts with other forms
        $randomId = Random::string(32);

        // Include task configuration file
        include(ROOT . '/config/tasks/' . $scheduleForm['action'] . '.php');

        if (in_array('unique', $formConfig['allowed-schedule-types']) and in_array('recurring', $formConfig['allowed-schedule-types'])) : ?>
            <div class="switch-field">
                <input type="radio" id="<?= $randomId ?>-task-schedule-type-unique" class="task-param" param-name="schedule-type" name="task-schedule-type" value="unique" checked />
                <label for="<?= $randomId ?>-task-schedule-type-unique">Unique task</label>
                <input type="radio" id="<?= $randomId ?>-task-schedule-type-recurring" class="task-param" param-name="schedule-type" name="task-schedule-type" value="recurring" />
                <label for="<?= $randomId ?>-task-schedule-type-recurring">Recurrent task</label>
            </div>
            <?php
        elseif (in_array('unique', $formConfig['allowed-schedule-types'])) : ?>
            <div class="single-switch-field">
                <input type="radio" id="<?= $randomId ?>-task-schedule-type-unique" class="task-param" param-name="schedule-type" name="task-schedule-type" value="unique" checked />
                <label for="<?= $randomId ?>-task-schedule-type-unique">Unique task</label>
            </div>
            <?php
        elseif (in_array('recurring', $formConfig['allowed-schedule-types'])) : ?>
            <div class="single-switch-field">
                <input type="radio" id="<?= $randomId ?>-task-schedule-type-recurring" class="task-param" param-name="schedule-type" name="task-schedule-type" value="recurring" checked />
                <label for="<?= $randomId ?>-task-schedule-type-recurring">Recurrent task</label>
            </div>
            <?php
        endif;

        if (in_array('recurring', $formConfig['allowed-schedule-types'])) : ?>
            <div class="task-schedule-recurring-frequency-input hide">
                <h6 class="required">FREQUENCY</h6>
                <select class="task-param" param-name="schedule-frequency">
                    <option value="">Select...</option>
                    <?php
                    foreach (['hourly', 'daily', 'weekly', 'monthly', 'cron'] as $frequency) {
                        echo '<option value="' . $frequency . '">' . ucfirst($frequency) . '</option>';
                    } ?>
                </select>
            </div>

            <div class="task-schedule-recurring-day-input hide">
                <h6 class="required">DAY(S)</h6>
                <select class="task-param" param-name="schedule-day" multiple>
                    <?php
                    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                        echo '<option value="' . $day . '">' . ucfirst($day) . '</option>';
                    } ?>
                </select>
            </div>

            <div class="task-schedule-recurring-monthly-input hide">
                <h6 class="required">ON THE</h6>
                <div class="flex justify-space-between column-gap-15">
                    <select class="task-param" param-name="schedule-monthly-day-position">
                        <?php
                        foreach (['first', 'second', 'third', 'last'] as $position) {
                            echo '<option value="' . $position . '">' . ucfirst($position) . '</option>';
                        } ?>
                    </select>

                    <select class="task-param" param-name="schedule-monthly-day">
                        <?php
                        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                            echo '<option value="' . $day . '">' . ucfirst($day) . '</option>';
                        } ?>
                    </select>

                </div>
                <p class="note">of the month</p>
            </div>

            <div class="task-schedule-recurring-cron-input hide">
                <h6 class="required">CRON EXPRESSION</h6>
                <p class="note">Format: minute hour day month weekday.</p>
                <input type="text" class="task-param" param-name="schedule-cron" placeholder="*/15 * * * *" />
            </div>
            <?php
        endif;

        if (in_array('unique', $formConfig['allowed-schedule-types'])) : ?>
            <div class="task-schedule-unique-input">
                <h6 class="required">DATE</h6>
                <input type="date" class="task-param" param-name="schedule-date" />
            </div>
            <?php
        endif; ?>

        <div class="task-schedule-time-input">
            <h6 class="required">TIME</h6>
            <input type="time" class="task-param" param-name="schedule-time" />
        </div>

        <h6>NOTIFY ON TASK ERROR</h6>
        <label class="onoff-switch-label">
            <input type="checkbox" class="onoff-switch-input task-param" param-name="schedule-notify-error" value="true" checked />
            <span class="onoff-switch-slider"></span>
        </label>

        <h6>NOTIFY ON TASK SUCCESS</h6>
        <label class="onoff-switch-label">
            <input type="checkbox" class="onoff-switch-input task-param" param-name="schedule-notify-success" value="true" checked />
            <span class="onoff-switch-slider"></span>
        </label>

        <h6>SEND A REMINDER</h6>
        <select class="task-param" param-name="schedule-reminder" multiple>
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
</div>

<script>
$(document).ready(function(){
    myselect2.convert('select.task-param[param-name="schedule-day"]', 'Select day(s)...', true);
    myselect2.convert('select.task-param[param-name="schedule-reminder"]', 'Select reminder...', true);
    myselect2.convert('select.task-param[param-name="schedule-recipient"]', 'Select or add recipients...', true);
});
</script>
