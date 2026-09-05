/**
 *  Event: view snapshot file content
 */
$(document).on('click','.view-file',function () {
    const name = $(this).attr('name');

    mymodal.loading();
    
    ajaxRequest(
        // Controller:
        'repo/snapshot/browse',
        // Action:
        'view-file',
        // Data:
        {
            path: $(this).attr('path')
        },
        // Print success alert:
        false,
        // Print error alert:
        true
    ).then(function () {
        // Print the modal window with the log
        mymodal.print(jsonValue.message, name, true);
    });
});

/**
 *  Event: show confirm box when selecting packages
 */
$(document).on('click',".package-checkbox",function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    const snapId = $('#packages-list').attr('snap-id');

    // The buttons that will be displayed in the confirm box
    var buttons = [];

    // If no checkbox is selected then hide the buttons
    if ($('body').find('input[name=packageName\\[\\]]:checked').length == 0) {
        myconfirmbox.close();
        return;
    }

    // Count the number of selected packages (across both tree and search results)
    var count = $('body').find('input[type="checkbox"].package-checkbox:checked').length;

    // Define confirm box buttons depending on the allowed actions

    // Download is always allowed by default
    buttons.push(
        {
            'text': 'Download',
            'callback': function () {
                mysnapshot.download();
            }
        }
    );

    if (myrepopermission.allowedAction('delete-package')) {
        buttons.push(
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    mysnapshot.delete(snapId);
                }
            }
        );
    }

    // Show the confirm box
    myconfirmbox.print(
        {
            'title': 'Select packages',
            'message': count + ' file(s) selected.',
            'id': 'select-package',
            'buttons': buttons
        }
    );
});

/**
 *  Event: submit the snapshot package upload form
 *  Captures the form submission and uploads the selected packages via ajax
 */
$(document).on('submit', '#snapshot-upload', function (e) {
    e.preventDefault();

    // Upload the selected packages
    mysnapshot.upload(this);
});

/**
 *  Event: rebuild metadata
 */
$(document).on('click',"#snapshot-rebuild-btn",function () {
    ajaxRequest(
        // Controller:
        'repo/snapshot/browse',
        // Action:
        'rebuild',
        // Data:
        {
            snapId: $(this).attr('snap-id'),
            gpgSign: $('input[type=checkbox][name=gpgSign]').is(':checked') ? 'true' : 'false'
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        // Reload containers
        mycontainer.reload('snapshot/kpi');
        mycontainer.reload('snapshot/list');
        // Close panel
        mypanel.close('snapshot/rebuild');
    });
});
