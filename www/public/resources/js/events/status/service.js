/**
 *  Event: view service unit log
 */
$(document).on('click', '.unit-log-view-btn', function(e) {
    mymodal.loading();

    const unit     = $(this).attr('unit');
    const logfile  = $('select[unit="' + unit + '"]').val();

    ajaxRequest(
        // Controller:
        'status/service',
        // Action:
        'get-unit-log',
        // Data:
        {
            unit: unit,
            logfile: logfile
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        mymodal.print(jsonValue.message, logfile, true);
    });
});

/**
 *  Event: show unit tooltip
 */
$(document).on('mouseenter', '.unit-tooltip', function (e) {
    const unit = $(this).attr('unit');
    const description = $(this).attr('description');
    const frequency = $(this).attr('frequency');
    const day = $(this).attr('day');
    const time = $(this).attr('time');
    let content = '';
    let freq = '';

    mytooltip.loading(e);

    content  = '<p><b>Unit</b></p>';
    content += '<p class="copy"><code>' + unit + '</code></p>';
    content += '<p class="margin-top-10"><b>Description</b></p>';
    content += '<p>' + description + '</p>';
    content += '<p class="margin-top-10"><b>Frequency</b></p>';

    if (frequency === 'every-minute') {
        freq += 'Every minute';
    }
    if (frequency === 'every-hour') {
        freq += 'Every hour';
    }
    if (frequency === 'every-day') {
        freq += 'Every day at ' + time;
    }
    if (frequency === 'every-week') {
        freq += 'Every week on ' + day + ' at ' + time;
    }
    if (frequency === 'forever') {
        freq += 'Constantly running';
    }

    content += '<p>' + freq + '</p>';

    // Print tooltip
    mytooltip.print(content, e);
});


