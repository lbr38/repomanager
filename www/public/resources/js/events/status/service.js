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
        mymodal.print(jsonValue.message, logfile, true, false);
    });
});

/**
 *  Event: show unit tooltip
 */
$(document).on('mouseenter', '.unit-tooltip', function (e) {
    const unit = $(this).attr('unit');
    const description = $(this).attr('description');

    mytooltip.loading(e);

    content  = '<p>Unit</p>';
    content += '<p class="copy"><code>' + unit + '</code></p>';

    content += '<p class="margin-top-10">Description</p>';
    content += '<p>' + description + '</p>';

    // Print tooltip
    mytooltip.print(content, e);
});


