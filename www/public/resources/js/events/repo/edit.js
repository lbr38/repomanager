/**
 *  Event: click on rename repository button
 */
$(document).on('click','.repo-rename-btn',function (e) {
    e.preventDefault(e);

    // The buttons that will be displayed in the confirm box
    var buttons = [];

    /**
     *  The list of allowed actions the user can execute on the selected repositories
     *  By default: all, unless the user has specific permissions
     *  Those permissions are later verified by the server so even if the user tries to execute an action he is not allowed to, it will not work
     */
    var allowedActions = ['update', 'duplicate', 'env', 'rebuild', 'rename', 'edit', 'install', 'delete'];

    // Get permissions from cookie
    if (mycookie.exists('user_permissions')) {
        var userPermissions = JSON.parse(mycookie.get('user_permissions'));

        // Reset allowed actions array
        var allowedActions = [];

        // Loop through all permissions and check if the user has the permission to execute the action
        if (userPermissions.repositories && userPermissions.repositories['allowed-actions']) {
            var allowedActions = userPermissions.repositories['allowed-actions'];
        }
    }

    if (!allowedActions.includes('rename')) {
        myalert.print('You do not have permission to rename repositories', 'error');
        return;
    }

    // Get panel
    mypanel.get('repos/rename', {
        repos: JSON.stringify([{
            'repo-id': $(this).attr('repo-id')
        }])
    });
});

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
        $('#repositories-list').find('input[name=checkbox-repo]').prop('checked', false);
        $('#repositories-list').find('input[name=checkbox-repo]').removeAttr('style');
    });

    return false;
});

/**
 *  Event: click on description or edit icon to edit it
 */
$(document).on('click','p.repo-description-input',function () {
    const $container = $(this).closest('.repo-description-container');
    const p = $container.find('p.repo-description-input');

    // If already in edit mode, do nothing
    if (p.find('input').length > 0) {
        return;
    }

    const currentDescription = p.text().trim();
    const repoId = p.attr('repo-id');
    const envId = p.attr('env-id');

    // Remove empty class (hide placeholder)
    p.removeClass('repo-description-empty');

    // Create input field
    const input = $('<input type="text" class="repo-description-input-edit">')
        .attr('repo-id', repoId)
        .attr('env-id', envId)
        .attr('data-original', currentDescription)
        .val(currentDescription);

    // Replace <p> content with input
    p.html(input);
    input.focus();
});

/**
 *  Event: edit repository description when pressing 'Enter' key
 */
$(document).on('keypress','.repo-description-input-edit',function (e) {
    e.stopPropagation();

    const keycode = (e.keyCode ? e.keyCode : e.which);

    if (keycode == '13') {
        const input = $(this);
        const p = input.closest('p.repo-description-input');
        const newDescription = input.val().trim();

        // Mark as saved to prevent blur from reverting
        input.data('saved', true);

        // Save description
        myrepo.updateDescription(input.attr('repo-id'), newDescription);

        // Revert to <p> with new value
        p.text(newDescription);

        // If description is now empty, re-add empty class
        if (!newDescription) {
            p.addClass('repo-description-empty');
        }
    }
});

/**
 *  Event: revert description input on blur (click outside)
 */
$(document).on('blur','.repo-description-input-edit',function () {
    const input = $(this);

    // If already saved via Enter, do nothing
    if (input.data('saved')) {
        return;
    }

    const p = input.closest('p.repo-description-input');
    const originalDescription = input.attr('data-original');

    // Revert to <p> with original value
    p.text(originalDescription);

    // If description is empty, re-add empty class
    if (!originalDescription) {
        p.addClass('repo-description-empty');
    }
});
