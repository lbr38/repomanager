/**
 *  Event: when we click on a checkbox, we show the 'Delete' and 'Download' buttons
 */
$(document).on('click',".package-checkbox",function () {
    // Count the number of checked checkbox
    var checked = $('body').find('input[name=packageName\\[\\]]:checked').length;
    var snapId = $('#packages-list').attr('snap-id');

    // If there is at least 1 checkbox selected then show the confirm box
    if (checked >= 1) {
        confirmBox(
            {
                'title': 'Select packages',
                'message': 'Select an action to perform on the selected packages:',
                'id': 'select-package',
                'buttons': [
                {
                    'text': 'Delete',
                    'color': 'red',
                    'callback': function () {
                        deletePackages(snapId);
                    }
                },
                {
                    'text': 'Download',
                    'color': 'blue-alt',
                    'callback': function () {
                        downloadPackage();
                    }
                }]
            }
        );
    }

    // If no checkbox is selected then we hide the buttons
    if (checked == 0) {
        closeConfirmBox();
    }
});

/**
 *  Event: rebuild metadata
 */
$(document).on('click',"#rebuild-btn",function () {
    var snapId = $(this).attr('snap-id');
    var gpgSign = 'false';

    if ($('input[type=checkbox][name=gpgSign]').is(':checked')) {
        var gpgSign = 'true';
    }

    ajaxRequest(
        // Controller:
        'browse',
        // Action:
        'rebuild',
        // Data:
        {
            snapId: snapId,
            gpgSign: gpgSign
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload containers:
        ['browse/list', 'browse/actions']
    );
});
