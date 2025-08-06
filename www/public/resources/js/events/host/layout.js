/**
 *  Event: switch to compact view
 */
$(document).on('click','#compact-view-btn',function () {
    // Get current view mode from cookie
    var viewMode = mycookie.get('hosts/compact-view');

    // If view mode is set to true, then set it to false
    if (viewMode == 1) {
        mycookie.set('hosts/compact-view', 0, 365);
    }

    // If there was no cookie or if view mode is set to false, then set it to true
    if (viewMode == "" || viewMode == 0) {
        mycookie.set('hosts/compact-view', 1, 365);
    }

    // Reload the container
    mycontainer.reload('hosts/list');
});

/**
 *  Event: click on 'Select all hosts' checkbox
 */
$(document).on('click', '#select-all-hosts', function () {
    /**
     *  Retrieve all hosts checkboxes
     */
    const hostsCheckboxes = $('input[type="checkbox"].js-select-all-button');

    /**
     *  Retrieve select status
     */
    const selectStatus = $(this).attr('status');

    /**
     *  If current status is not 'selected', then select all hosts
     */
    if (selectStatus != 'selected') {
        hostsCheckboxes.each(function () {
            if (!$(this).is(':checked')) {
                $(this).click();
            }
        });

        // Set status to 'selected'
        $(this).attr('status', 'selected');

        // Make sure the 'Select all hosts' button is visible and is checked
        $(this).css('opacity', '1');
        $(this).css('filter', 'initial');
        $(this).find('input[type="checkbox"]').prop('checked', true);

    /**
     *  Otherwise, unselect all hosts
     */
    } else {
        hostsCheckboxes.each(function () {
            if ($(this).is(':checked')) {
                $(this).click();
            }
        });

        // Set status to 'unselected'
        $(this).attr('status', 'unselected');

        // Make sure the 'Select all hosts' button is unchecked
        $(this).css('opacity', '');
        $(this).css('filter', '');
        $(this).find('input[type="checkbox"]').prop('checked', false);
    }
});
