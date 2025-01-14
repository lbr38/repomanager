/**
 *  Event: submit reposiroty edit form
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
