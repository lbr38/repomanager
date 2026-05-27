<?php ob_start(); ?>

<p class="note">Schedule a task on the <b>latest snapshot</b> of every repository matching the filters below.</p>
<p class="note margin-bottom-10">Repositories created later are automatically included, so the task never has to be recreated.</p>

<form id="scheduled-task-form" autocomplete="off">
    <div class="scheduled-task-form-params form-block">
        <h6 class="required">ACTION</h6>
        <select id="scheduled-task-action" class="scheduled-task-param" param-name="action">
            <?php
            foreach ($allowedActions as $action) {
                echo '<option value="' . $action . '">' . $actionsTitles[$action] . '</option>';
            } ?>
        </select>

        <h6>TARGET</h6>
        <p class="note"><b>By default, all repositories are targeted.</b> Use the filters below to restrict the target. Leave a filter empty to not filter on it, filters are combined.</p>

        <h6>REPOSITORY GROUP</h6>
        <select id="scheduled-task-target-group" class="scheduled-task-target-param" param-name="group">
            <option value="">No group filter (all repositories)</option>
            <?php
            foreach ($groups as $group) {
                echo '<option value="' . intval($group['Id']) . '">' . htmlspecialchars($group['Name']) . '</option>';
            } ?>
        </select>

        <h6>TAGS</h6>
        <p class="note">Only repositories having <b>all</b> the selected tags will be targeted.</p>
        <select id="scheduled-task-target-tags" class="scheduled-task-target-param" param-name="tags" multiple>
            <?php
            foreach ($tags as $tag) {
                echo '<option value="' . htmlspecialchars($tag, ENT_QUOTES) . '">' . htmlspecialchars($tag) . '</option>';
            } ?>
        </select>

        <h6>PACKAGE TYPE</h6>
        <select id="scheduled-task-target-package-type" class="scheduled-task-target-param" param-name="package-type">
            <option value="">No package type filter</option>
            <option value="deb">deb</option>
            <option value="rpm">rpm</option>
        </select>

        <div class="flex align-item-center column-gap-5 margin-top-15">
            <img src="/assets/icons/info.svg" class="icon-np" />
            <p id="scheduled-task-target-count">Calculating...</p>
        </div>

        <!-- Parameters for the 'update' action -->
        <div class="scheduled-task-action-params" action="update">
            <h6>POINT AN ENVIRONMENT</h6>
            <p class="note">Optional. Select one or multiple environments to point to each new snapshot.</p>
            <select id="scheduled-task-update-env" class="scheduled-task-param" param-name="env" multiple>
                <?php
                foreach (ENVS as $env) {
                    echo '<option value="' . htmlspecialchars($env['Name'], ENT_QUOTES) . '">' . htmlspecialchars($env['Name']) . '</option>';
                } ?>
            </select>

            <h6>CHECK GPG SIGNATURES</h6>
            <p class="note">Check GPG signature of repository / packages. Only applies to mirror repositories.</p>
            <label class="onoff-switch-label">
                <input type="checkbox" class="onoff-switch-input scheduled-task-param" param-name="gpg-check" value="true" checked />
                <span class="onoff-switch-slider"></span>
            </label>

            <h6>SIGN WITH GPG</h6>
            <p class="note">Sign repository / packages with GPG.</p>
            <label class="onoff-switch-label">
                <input type="checkbox" class="onoff-switch-input scheduled-task-param" param-name="gpg-sign" value="true" checked />
                <span class="onoff-switch-slider"></span>
            </label>

            <p class="note margin-top-15">
                <img src="/assets/icons/info.svg" class="icon-np" />
                Each repository is updated using its own architectures and advanced parameters.
            </p>
        </div>

        <!-- Parameters for the 'env' action -->
        <div class="scheduled-task-action-params hide" action="env">
            <h6 class="required">ENVIRONMENT</h6>
            <p class="note">Select one or multiple environments to point to the latest snapshot of each repository.</p>
            <select id="scheduled-task-env-env" class="scheduled-task-param" param-name="env" multiple>
                <?php
                foreach (ENVS as $env) {
                    echo '<option value="' . htmlspecialchars($env['Name'], ENT_QUOTES) . '">' . htmlspecialchars($env['Name']) . '</option>';
                } ?>
            </select>
        </div>

        <!-- Parameters for the 'rebuild' action -->
        <div class="scheduled-task-action-params hide" action="rebuild">
            <h6>SIGN WITH GPG</h6>
            <p class="note">Sign repository / packages with GPG.</p>
            <label class="onoff-switch-label">
                <input type="checkbox" class="onoff-switch-input scheduled-task-param" param-name="gpg-sign" value="true" checked />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>
    </div>

    <?php
    // Reuse the same scheduling form as regular tasks (avoid code duplication)
    $scheduleForm = [
        'action' => 'update',
        'show-intro' => false,
        'show-toggle' => false
    ];

    include(ROOT . '/views/includes/forms/tasks/schedule.inc.php'); ?>

    <br>
    <button type="button" class="scheduled-task-confirm-btn btn-large-green">Schedule task</button>
</form>
<br><br>

<script>
$(document).ready(function () {
    myselect2.convert('#scheduled-task-target-tags', 'Select tag(s)...', true);
    myselect2.convert('#scheduled-task-update-env', 'Select environment(s)...');
    myselect2.convert('#scheduled-task-env-env', 'Select environment(s)...');

    // Show the parameters of the selected action and refresh the number of targeted repositories
    myscheduledtask.initScheduleForm();
    myscheduledtask.printActionParams();
    myscheduledtask.refreshCount();
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/scheduled-task';
$slidePanelTitle = 'SCHEDULE A TASK';

include(ROOT . '/views/includes/slide-panel.inc.php');
