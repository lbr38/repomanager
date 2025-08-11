/**
 *  Event: install packages on one host
 */
$(document).on('click','.host-install-packages-btn',function () {
    var id = $(this).attr('host-id');

    mypanel.get('hosts/requests/install-packages', {hostId: id});
});

/**
 *  Event: install packages on multiple selected hosts
 */
$(document).on('click','.hosts-install-packages-btn',function () {
    var hosts = [];

    // Get all checked checkbox
    $('input[type="checkbox"][name="checkbox-host[]"]').each(function () {
        // If checkbox is checked then add host id to hosts array
        if (this.checked) {
            hosts.push($(this).val());
        }
    });

    mypanel.get('hosts/requests/install-packages', {hostsId: hosts});
});

/**
 *  Event: click on 'Install' button to install packages
 */
$(document).on('submit', '#host-install-packages-form', function () {
    event.preventDefault();

    var obj = {};

    /**
     *  Retrieve all parameters from the form
     */
    $(this).find('.request-param').each(function () {
        /**
         *  Retrieve the parameter name (input name) and its value (input value)
         */
        var name = $(this).attr('param-name');

        /**
         *  If the input is a checkbox and it is checked then its value will be 'true'
         *  If it is not checked then its value will be 'false'
         */
        if ($(this).attr('type') == 'checkbox') {
            if ($(this).is(":checked")) {
                var value = 'true';
            } else {
                var value = 'false';
            }

        /**
         *  If the input is a radio button then we only retrieve its value if it is checked, otherwise we move on to the next parameter
         */
        } else if ($(this).attr('type') == 'radio') {
            if ($(this).is(":checked")) {
                var value = $(this).val();
            } else {
                return; // return is the equivalent of 'continue' for jquery loops .each()
            }
        } else {
            /**
             *  If the input is not a checkbox then we retrieve its value
             */
            var value = $(this).val();
        }

        obj[name] = value;
    });

    ajaxRequest(
        // Controller:
        'host/execute',
        // Action:
        'install-packages',
        // Data:
        {
            params: obj
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        [],
        // Execute functions on success:
        []
    );

    return false;
});

/**
 *  Event: update packages on one host
 */
$(document).on('click','.host-update-packages-btn',function () {
    var id = $(this).attr('host-id');

    mypanel.get('hosts/requests/update-packages', {hostId: id});
});

/**
 *  Event: when a host checkbox is checked
 */
$(document).on('click','input[type="checkbox"][name="checkbox-host[]"]',function () {
    // Print actions for selected hosts
    myhost.getActionBox();
});

/**
 *  Event: when a 'Select all' button is clicked, it selects all checkbox-host[] of the group
 */
$(document).on('click','input[type="checkbox"].select-group-hosts-checkbox',function () {
    // Retrieve the group name of the button which has been clicked
    const group = $(this).attr('group');

    // Retrieve checkbox status
    const status = $(this).attr('status');

    // Retrieve all checkboxes of the group
    const hostsCheckboxes = $('input[name="checkbox-host[]"][group="' + group + '"]:visible');

    // If current status is 'selected', then unselect all hosts
    if (status == 'selected') {
        hostsCheckboxes.each(function () {
            if ($(this).is(':checked')) {
                $(this).prop('checked', false);
            }
        });

        // Set status to 'unselected'
        $(this).attr('status', 'unselected');

    // Make sure the 'Select all hosts' button is unchecked
    } else {
        // Check all checkbox-host[] of the same group
        hostsCheckboxes.each(function () {
            if (!$(this).is(':checked')) {
                $(this).prop('checked', true);
            }
        });

        // Set status to 'selected'
        $(this).attr('status', 'selected');
    }

    // Print actions for selected hosts
    myhost.getActionBox();
});

/**
 *  Event: click on 'Select all hosts' checkbox
 */
$(document).on('click', '#select-all-hosts', function () {
    /**
     *  Retrieve all host groups checkboxes that are visible
     */
    const hostGroupsCheckboxes = $('input[type="checkbox"].select-group-hosts-checkbox:visible');

    /**
     *  Retrieve all hosts checkboxes that are visible
     */
    const hostCheckboxes = $('input[type="checkbox"][name="checkbox-host[]"]:visible');

    /**
     *  Retrieve select status
     */
    const selectStatus = $(this).attr('status');

    /**
     *  If current status is not 'selected', then select all hosts
     */
    if (selectStatus != 'selected') {
        hostGroupsCheckboxes.each(function () {
            if (!$(this).is(':checked')) {
                $(this).prop('checked', true);
            }
        });

        hostCheckboxes.each(function () {
            if (!$(this).is(':checked')) {
                $(this).prop('checked', true);
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
        hostGroupsCheckboxes.each(function () {
            if ($(this).is(':checked')) {
                $(this).prop('checked', false);
            }
        });

        hostCheckboxes.each(function () {
            if ($(this).is(':checked')) {
                $(this).prop('checked', false);
            }
        });

        // Set status to 'unselected'
        $(this).attr('status', 'unselected');

        // Make sure the 'Select all hosts' button is unchecked
        $(this).css('opacity', '');
        $(this).css('filter', '');
        $(this).find('input[type="checkbox"]').prop('checked', false);
    }

    // Print actions for selected hosts
    myhost.getActionBox();
});

/**
 *  Event: select package update type
 */
$(document).on('change','input[type="radio"][param-name="update-type"]',function () {
    // Show / hide the div containing the list of packages to update
    if ($('input[type="radio"]#update-all-pkg').is(':checked')) {
        $('div#update-specific-pkg-div').hide();
    } else {
        $('div#update-specific-pkg-div').show();
    }
});

/**
 *  Event: click on 'Update' button to update packages
 */
$(document).on('submit', '#host-update-packages-form', function () {
    event.preventDefault();

    var obj = {};

    /**
     *  Retrieve all parameters from the form
     */
    $(this).find('.request-param').each(function () {
        /**
         *  Retrieve the parameter name (input name) and its value (input value)
         */
        var name = $(this).attr('param-name');

        /**
         *  If the input is a checkbox and it is checked then its value will be 'true'
         *  If it is not checked then its value will be 'false'
         */
        if ($(this).attr('type') == 'checkbox') {
            if ($(this).is(":checked")) {
                var value = 'true';
            } else {
                var value = 'false';
            }

        /**
         *  If the input is a radio button then we only retrieve its value if it is checked, otherwise we move on to the next parameter
         */
        } else if ($(this).attr('type') == 'radio') {
            if ($(this).is(":checked")) {
                var value = $(this).val();
            } else {
                return; // return is the equivalent of 'continue' for jquery loops .each()
            }
        } else {
            /**
             *  If the input is not a checkbox then we retrieve its value
             */
            var value = $(this).val();
        }

        obj[name] = value;
    });

    ajaxRequest(
        // Controller:
        'host/execute',
        // Action:
        'update-packages',
        // Data:
        {
            params: obj
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        [],
        // Execute functions on success:
        []
    );

    return false;
});

/**
 *  Event: available package checkbox selection
 */
$(document).on('click','input[type="checkbox"].available-package-checkbox',function (e) {
    // Prevent parent to be triggered
    e.stopPropagation();

    /**
     *  Retrieve host Id
     */
    var hostId = $(this).attr('host-id');

    /**
     *  If a cookie exists, retrieve it
     */
    if (mycookie.get('temp/host-av-package-selected')) {
        var packages = JSON.parse(mycookie.get('temp/host-av-package-selected'));
    } else {
        var packages = {
            packages: []
        }
    }

    /**
     *  Append the package to the array if the checkbox is checked
     *  Otherwise remove the package from the array
     */
    if (this.checked) {
        /**
         *  Check if the package is already in the array
         */
        for (var i = 0; i < packages['packages'].length; i++) {
            if (packages['packages'][i]['name'] == $(this).attr('package')) {
                return;
            }
        }

        packages['packages'].push({
            name: $(this).attr('package'),
            available_version: $(this).attr('version')
        });
    } else {
        for (var i = 0; i < packages['packages'].length; i++) {
            if (packages['packages'][i]['name'] == $(this).attr('package')) {
                packages['packages'].splice(i, 1);
            }
        }
    }

    /**
     *  Save the array in a cookie
     */
    mycookie.set('temp/host-av-package-selected', JSON.stringify(packages), 1);

    /**
     *  Count the number of checked checkboxes
     */
    var countChecked = $('input[type="checkbox"].available-package-checkbox:checked').length;

    /**
     *  If number of checked checkboxes > 0 then display the action button
     */
    if (countChecked > 0) {
        myconfirmbox.print(
            {
                'title': 'Update packages',
                'message': 'Request the host to update selected packages?',
                'id': 'update-available-packages-confirm-box',
                'buttons': [
                {
                    'text': 'Request',
                    'color': 'red',
                    'callback': function () {
                        ajaxRequest(
                            // Controller:
                            'host/execute',
                            // Action:
                            'update-selected-available-packages',
                            // Data:
                            {
                                hostId: hostId,
                                packages: packages
                            },
                            // Print success alert:
                            true,
                            // Print error alert:
                            true,
                            // Reload container:
                            [],
                            // Execute functions on success:
                            []
                        );

                        // Remove cookie
                        mycookie.set('temp/host-av-package-selected', '', -1);
                    }
                }]
            }
        );
    } else {
        myconfirmbox.close();
    }
});

/**
 *  Event: when host reset button is clicked
 */
$(document).on('click','#host-reset-btn',function () {
    var id = $(this).attr('host-id');

    myconfirmbox.print(
        {
            'title': 'Reset host',
            'message': 'Do you really want to reset the host informations?',
            'buttons': [
            {
                'text': 'Reset',
                'color': 'red',
                'callback': function () {
                    executeAction('reset', [id]);
                }
            }]
        }
    );
});

/**
 *  Event: when host delete button is clicked
 */
$(document).on('click','#host-delete-btn',function () {
    var id = $(this).attr('host-id');

    myconfirmbox.print(
        {
            'title': 'Delete host',
            'message': 'Do you really want to delete the host?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    executeAction('delete', [id]);
                }
            }]
        }
    );
});

/**
 *  Event: when a single host action button is clicked
 */
$(document).on('click','#host-request-btn',function () {
    var id = $(this).attr('host-id');

    myconfirmbox.print(
        {
            'title': 'Request',
            'message': 'Select a request to send to the host',
            'buttons': [
            {
                'text': 'Request general informations',
                'color': 'blue-alt',
                'callback': function () {
                    executeAction('request-general-infos', [id]);
                }
            },
            {
                'text': 'Request packages informations',
                'color': 'blue-alt',
                'callback': function () {
                    executeAction('request-packages-infos', [id]);
                }
            },
            {
                'text': 'Update packages',
                'color': 'blue-alt',
                'callback': function () {
                    mypanel.get('hosts/requests/update-packages', {
                        hostsId: [id]
                    });
                }
            }]
        }
    );
});