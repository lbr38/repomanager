<hr>

<div class="task-schedule-form-params">
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
                <div class="switch-field">
                    <input type="radio" id="task-schedule-type-unique" class="task-param" param-name="schedule-type" name="task-schedule-type" value="unique" checked />
                    <label for="task-schedule-type-unique">Unique task</label>
                    <input type="radio" id="task-schedule-type-recurrent" class="task-param" param-name="schedule-type" name="task-schedule-type" value="recurrent" />
                    <label for="task-schedule-type-recurrent">Recurrent task</label>
                </div>
            </td>
        </tr>

        <tr class="task-schedule-recurrent-input hide">
            <td class="td-10">Frequency</td>
            <td>
                <select class="task-param" param-name="schedule-frequency">
                    <option value="">Select...</option>
                    <option value="hourly">Hourly</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                </select>
            </td>
        </tr>

        <tr class="task-schedule-recurrent-day-input hide">
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

        <tr class="task-schedule-unique-input">
            <td class="td-10">Date</td>
            <td>
                <input type="date" class="task-param" param-name="schedule-date" />
            </td>
        </tr>

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