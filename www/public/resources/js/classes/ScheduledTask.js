class ScheduledTask
{
    /**
     *  This panel only supports scheduled tasks: force and lock the scheduling switch.
     */
    initScheduleForm()
    {
        const scheduleInput = $('.task-schedule-form-params .task-schedule-btn');

        if (scheduleInput.length) {
            scheduleInput.prop('checked', true).trigger('change');
            scheduleInput.prop('disabled', true);
            scheduleInput.closest('.onoff-switch-label').addClass('nopointer lowopacity');
        }
    }

    /**
     *  Return the action currently selected in the scheduled task panel
     */
    getAction()
    {
        return $('#scheduled-task-action').val();
    }

    /**
     *  Show only the parameters related to the selected action
     */
    printActionParams()
    {
        const action = this.getAction();

        $('.scheduled-task-action-params').hide();
        $('.scheduled-task-action-params[action="' + action + '"]').show();
    }

    /**
     *  Return the target definition (repositories filters) entered by the user
     */
    getTarget()
    {
        var target = {};

        $('.scheduled-task-target-param').each(function () {
            const name = $(this).attr('param-name');
            var value = $(this).val();

            // Multiple selects return null when nothing is selected
            if (value == null) {
                value = $(this).prop('multiple') ? [] : '';
            }

            target[name] = value;
        });

        return target;
    }

    /**
     *  Print a description of the repositories currently matching the target
     */
    printTarget(description)
    {
        $('#scheduled-task-target-count').html(description);
    }

    /**
     *  Retrieve and print a description of the repositories currently matching the target
     */
    refreshCount()
    {
        $('#scheduled-task-target-count').text('Calculating...');

        ajaxRequest(
            // Controller:
            'task',
            // Action:
            'count-target-repos',
            // Data:
            {
                target: JSON.stringify(this.getTarget())
            },
            // Print success alert:
            false,
            // Print error alert:
            false,
            // Reload containers:
            null,
            // Exec on success:
            ['myscheduledtask.printTarget(jsonValue.message)'],
            // Exec on error:
            ['$("#scheduled-task-target-count").text("Could not retrieve the targeted repositories.")']
        );
    }

    /**
     *  Build the task parameters from the panel form
     */
    getParams()
    {
        const action = this.getAction();

        var params = {
            action: action,
            target: this.getTarget(),
            schedule: {
                scheduled: 'true'
            }
        };

        // Retrieve the parameters of the selected action only
        $('.scheduled-task-action-params[action="' + action + '"]').find('.scheduled-task-param').each(function () {
            const name = $(this).attr('param-name');

            if ($(this).attr('type') == 'checkbox') {
                params[name] = $(this).is(':checked') ? 'true' : 'false';
                return;
            }

            var value = $(this).val();

            if (value == null) {
                value = $(this).prop('multiple') ? [] : '';
            }

            params[name] = value;
        });

        // Retrieve the scheduling parameters
        $('.task-schedule-form-params').find('.task-param').each(function () {
            const name = $(this).attr('param-name');

            if ($(this).attr('type') == 'checkbox') {
                params['schedule'][name] = $(this).is(':checked') ? 'true' : 'false';
                return;
            }

            if ($(this).attr('type') == 'radio') {
                if ($(this).is(':checked')) {
                    params['schedule'][name] = $(this).val();
                }
                return;
            }

            var value = $(this).val();

            if (value == null) {
                value = $(this).prop('multiple') ? [] : '';
            }

            params['schedule'][name] = value;
        });

        return params;
    }

    /**
     *  Create the scheduled task
     */
    schedule()
    {
        ajaxRequest(
            // Controller:
            'task',
            // Action:
            'validate-execute-target',
            // Data:
            {
                taskParams: JSON.stringify(this.getParams())
            },
            // Print success alert:
            true,
            // Print error alert:
            true,
            // Reload containers:
            null,
            // Exec on success:
            ['mypanel.close("repos/scheduled-task")']
        );
    }
}
