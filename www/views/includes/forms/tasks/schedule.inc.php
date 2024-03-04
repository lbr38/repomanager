<hr>

<div class="task-schedule-form-params" action="<?= $scheduleForm['action'] ?>">
    <table>
        <tr>
            <td colspan="100%"><b>Task scheduling</b></td>
        </tr>

        <tr>
            <td class="td-30">Schedule it</td>
            <td>
                <label class="onoff-switch-label">
                    <input type="checkbox" id="task-schedule-btn" class="onoff-switch-input task-param" param-name="scheduled" value="true" />
                    <span class="onoff-switch-slider"></span>
                </label>
            </td>
        </tr>

        <!-- Scheduling params -->
        <tr>
            <td class="td-30">Schedule type</td>
            <td>
                <?php
                /**
                 *  Generate a random string to make radio Id unique and avoid conflicts with other forms
                 */
                $randomId = \Controllers\Common::randomString(32);

                if (in_array('unique', $scheduleForm['type']) and in_array('recurring', $scheduleForm['type'])) : ?>
                    <div class="switch-field">
                        <input type="radio" id="<?= $randomId ?>-task-schedule-type-unique" class="task-param" action="<?= $scheduleForm['action'] ?>" param-name="schedule-type" name="task-schedule-type" value="unique" checked />
                        <label for="<?= $randomId ?>-task-schedule-type-unique">Unique task</label>
                        <input type="radio" id="<?= $randomId ?>-task-schedule-type-recurring" class="task-param" action="<?= $scheduleForm['action'] ?>" param-name="schedule-type" name="task-schedule-type" value="recurring" />
                        <label for="<?= $randomId ?>-task-schedule-type-recurring">Recurrent task</label>
                    </div>
                    <?php
                elseif (in_array('unique', $scheduleForm['type'])) : ?>
                    <div class="single-switch-field">
                        <input type="radio" id="<?= $randomId ?>-task-schedule-type-unique" class="task-param" action="<?= $scheduleForm['action'] ?>" param-name="schedule-type" name="task-schedule-type" value="unique" checked />
                        <label for="<?= $randomId ?>-task-schedule-type-unique">Unique task</label>
                    </div>
                    <?php
                elseif (in_array('recurring', $scheduleForm['type'])) : ?>
                    <div class="single-switch-field">
                        <input type="radio" id="<?= $randomId ?>-task-schedule-type-recurring" class="task-param" action="<?= $scheduleForm['action'] ?>" param-name="schedule-type" name="task-schedule-type" value="recurring" checked />
                        <label for="<?= $randomId ?>-task-schedule-type-recurring">Recurrent task</label>
                    </div>
                    <?php
                endif; ?>
            </td>
        </tr>

        <?php
        if (in_array('recurring', $scheduleForm['type'])) : ?>
            <tr class="task-schedule-recurring-frequency-input hide">
                <td class="td-10">Frequency</td>
                <td>
                    <select class="task-param" param-name="schedule-frequency">
                        <option value="">Select...</option>
                        <option value="hourly">Hourly</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </td>
            </tr>

            <tr class="task-schedule-recurring-day-input hide">
                <td class="td-10">Day(s)</td>
                <td>
                    <select class="task-param" param-name="schedule-day" multiple>
                        <option value="monday">Monday</option>
                        <option value="tuesday">Tuesday</option>
                        <option value="wednesday">Wednesday</option>
                        <option value="thursday">Thursday</option>
                        <option value="friday">Friday</option>
                        <option value="saturday">Saturday</option>
                        <option value="sunday">Sunday</option>
                    </select>
                </td>
            </tr>

            <tr class="task-schedule-recurring-monthly-input hide">
                <td class="td-10">On the</td>
                <td>
                    <select class="task-param select-small" param-name="schedule-monthly-day-position">
                        <option value="first">First</option>
                        <option value="second">Second</option>
                        <option value="third">Third</option>
                        <option value="last">Last</option>
                    </select>

                    <select class="task-param select-small" param-name="schedule-monthly-day">
                        <option value="monday">Monday</option>
                        <option value="tuesday">Tuesday</option>
                        <option value="wednesday">Wednesday</option>
                        <option value="thursday">Thursday</option>
                        <option value="friday">Friday</option>
                        <option value="saturday">Saturday</option>
                        <option value="sunday">Sunday</option>
                    </select>

                    <span>of the month</span>
                </td>
            </tr>            
            <?php
        endif;

        if (in_array('unique', $scheduleForm['type'])) : ?>
            <tr class="task-schedule-unique-input">
                <td class="td-10">Date</td>
                <td>
                    <input type="date" class="task-param" param-name="schedule-date" />
                </td>
            </tr>
            <?php
        endif; ?>

        <tr class="task-schedule-time-input">
            <td class="td-10">Time</td>
            <td>
                <input type="time" class="task-param" param-name="schedule-time" />
            </td>
        </tr>

        <tr>
            <td class="td-10">Notify on task error</td>
            <td>
                <label class="onoff-switch-label">
                    <input type="checkbox" class="onoff-switch-input task-param" param-name="schedule-notify-error" value="true" checked />
                    <span class="onoff-switch-slider"></span>
                </label>
            </td>
        </tr>

        <tr>
            <td class="td-10">Notify on task success</td>
            <td>
                <label class="onoff-switch-label">
                    <input type="checkbox" class="onoff-switch-input task-param" param-name="schedule-notify-success" value="true" checked />
                    <span class="onoff-switch-slider"></span>
                </label>
            </td>
        </tr>

        <tr>
            <td class="td-10">Send a reminder</td>
            <td>
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
            </td>
        </tr>

        <tr>
            <td>Recipient(s)</td>
            <td>
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
            </td>
        </tr>
    </table>
</div>

<script>
$(document).ready(function(){
    selectToSelect2('select.task-param[param-name="schedule-day"]', 'Select day(s)...', true);
    selectToSelect2('select.task-param[param-name="schedule-reminder"]', 'Select reminder...', true);
    selectToSelect2('select.task-param[param-name="schedule-recipient"]', 'Select or add recipients...', true);
});
</script>