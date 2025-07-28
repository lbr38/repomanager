/**
 *  Event: when we click on a checkbox, we show the 'Delete' and 'Download' buttons
 */
$(document).on('click',".package-checkbox",function () {
    var snapId = $('#packages-list').attr('snap-id');

    /**
     *  The list of allowed actions the user can execute on the selected repositories
     *  By default: all (only 'delete-package' for now but maybe more later), unless the user has specific permissions
     *  Those permissions are later verified by the server so even if the user tries to execute an action he is not allowed to, it will not work
     */
    var allowedActions = ['delete-package'];

    /**
     *  The buttons that will be displayed in the confirm box
     */
    var buttons = [];

    /**
     *  If no checkbox is selected then hide the buttons
     */
    if ($('body').find('input[name=packageName\\[\\]]:checked').length == 0) {
        myconfirmbox.close();
        return;
    }

    /**
     *  Get permissions from cookie
     */
    if (mycookie.exists('user_permissions')) {
        var userPermissions = JSON.parse(mycookie.get('user_permissions'));

        // Reset allowed actions array
        var allowedActions = [];

        // Loop through all permissions and check if the user has the permission to execute the action
        if (userPermissions.repositories && userPermissions.repositories['allowed-actions'] && userPermissions.repositories['allowed-actions']['repos']) {
            var allowedActions = userPermissions.repositories['allowed-actions']['repos'];
        }
    }

    /**
     *  Define confirm box buttons depending on the allowed actions
     */

    // Download is always allowed by default
    buttons.push(
        {
            'text': 'Download',
            'color': 'blue-alt',
            'callback': function () {
                downloadPackage();
            }
        }
    );

    if (allowedActions.includes('delete-package')) {
        buttons.push(
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    deletePackages(snapId);
                }
            }
        );
    }

    /**
     *  Show the confirm box
     */
    myconfirmbox.print(
        {
            'title': 'Select packages',
            'message': 'Select an action to perform on the selected packages:',
            'id': 'select-package',
            'buttons': buttons
        }
    );
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
