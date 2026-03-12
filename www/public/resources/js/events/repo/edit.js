/**
 *  Event: click on edit repository button
 *  TODO: wait for repos list refactoring before implementing the repo-edit-btn
 */
// $(document).on('click','.repo-edit-btn',function (e) {
//     e.preventDefault();

//     const repoId = $(this).attr('repo-id');

//     /**
//      *  The buttons that will be displayed in the confirm box
//      */
//     var buttons = [];

//     /**
//      *  The list of allowed actions the user can execute on the selected repositories
//      *  By default: all, unless the user has specific permissions
//      *  Those permissions are later verified by the server so even if the user tries to execute an action he is not allowed to, it will not work
//      */
//     var allowedActions = ['update', 'duplicate', 'env', 'rebuild', 'rename', 'edit', 'install', 'delete'];

//     /**
//      *  Get permissions from cookie
//      */
//     if (mycookie.exists('user_permissions')) {
//         var userPermissions = JSON.parse(mycookie.get('user_permissions'));

//         // Reset allowed actions array
//         var allowedActions = [];

//         // Loop through all permissions and check if the user has the permission to execute the action
//         if (userPermissions.repositories && userPermissions.repositories['allowed-actions']) {
//             var allowedActions = userPermissions.repositories['allowed-actions'];
//         }
//     }

//     /**
//      *  Define confirm box buttons depending on the allowed actions
//      */
//     if (allowedActions.includes('rename')) {
//         buttons.push(
//             {
//                 'text': 'Rename',
//                 'color': 'blue-alt',
//                 'callback': function () {
//                     executeAction('rename');
//                 }
//             }
//         );
//     }

//     if (allowedActions.includes('edit')) {
//         buttons.push(
//             {
//                 'text': 'Edit',
//                 'color': 'blue-alt',
//                 'callback': function () {
//                     executeAction('edit')
//                 }
//             }
//         );
//     }

//     myconfirmbox.print(
//         {
//             'title': 'Edit repository',
//             'message': '',
//             'id': 'repo-edit-confirm-box',
//             'buttons': buttons
//         }
//     );
// });

/**
 *  Event: submit repository edit form
 */
$(document).on('submit','#edit-form',function () {
    event.preventDefault();

    /**
     *  Main array that will contain all the parameters of each repo to be processed (1 or more repos depending on the user's selection)
     */
    var params = [];

    /**
     *  Retrieve the parameters entered in the form
     */
    $(this).find('.edit-form-params').each(function () {
        /**
         *  Object that will contain the parameters entered in the form for this repo
         */
        var obj = {};

        /**
         *  Retrieve the repo-id and snap-id of the repo to be processed
         */
        obj['repo-id'] = $(this).attr('repo-id');
        obj['snap-id'] = $(this).attr('snap-id');

        /**
         *  Retrieve the parameters entered by the user and push them into the object
         *  There is no associative array in js so we push an object.
         */
        $(this).find('.edit-param').each(function () {
            /**
             *  Retrieve the parameter name (input name) and its value (input value)
             */
            var param_name = $(this).attr('param-name');

            /**
             *  If the input is a checkbox and it is checked then its value will be 'true'
             *  If it is not checked then its value will be 'false'
             */
            if ($(this).attr('type') == 'checkbox') {
                if ($(this).is(":checked")) {
                    var param_value = 'true';
                } else {
                    var param_value = 'false';
                }

            /**
             *  If the input is a radio button then we only retrieve its value if it is checked, otherwise we move on to the next parameter
             */
            } else if ($(this).attr('type') == 'radio') {
                if ($(this).is(":checked")) {
                    var param_value = $(this).val();
                } else {
                    return; // return is the equivalent of 'continue' for jquery loops .each()
                }
            } else {
                /**
                 *  If the input is not a checkbox then we retrieve its value
                 */
                var param_value = $(this).val();
            }

            obj[param_name] = param_value;
        });

        /**
         *  Push each repo parameter into the main array
         */
        params.push(obj);
    });

    /**
     *  Convert the main array to JSON format and send it to php for verification of the parameters
     */
    var paramsJson = JSON.stringify(params);

    // for debug only
    // console.log(paramsJson);

    ajaxRequest(
        // Controller:
        'repo/edit',
        // Action:
        'validateForm',
        // Data:
        {
            params: paramsJson,
        },
        // Print success alert:
        true,
        // Print error alert:
        true
    ).then(function () {
        // Uncheck all checkboxes and remove all styles JQuery could have applied
        $('.reposList').find('input[name=checkbox-repo]').prop('checked', false);
        $('.reposList').find('input[name=checkbox-repo]').removeAttr('style');
    });

    return false;
});

/**
 *  Event: add placeholder to description input on mouse enter
 *  This is to prevent Firefox from always displaying the placeholder
 */
$(document).on('mouseenter','input[type="text"].repo-description-input',function () {
    $(this).attr('placeholder', '🖉 add a description');
});

/**
 *  Event: remove placeholder on mouse leave
 *  This is to prevent Firefox from always displaying the placeholder
 */
$(document).on('mouseleave','input[type="text"].repo-description-input',function () {
    $(this).attr('placeholder', '');
});

/**
 *  Event: edit repository description when pressing 'Enter' key
 */
$(document).on('keypress','input[type="text"].repo-description-input',function (e) {
    e.stopPropagation();

    const keycode = (e.keyCode ? e.keyCode : e.which);

    if (keycode == '13') {
        myenvironment.updateDescription($(this).attr('env-id'), $(this).val());
    }
});

/**
 *  Event: create new repo: print description field only if an env is specified
 */
$(document).on('change','#new-repo-target-env-select',function () {
    if ($('#new-repo-target-env-select').val() == "") {
        $('#new-repo-target-description-tr').hide();
    } else {
        $('#new-repo-target-description-tr').show();
    }
}).trigger('change');
