/**
 *  Event: Edit hosts settings
 */
$(document).on('submit','form#hosts-settings',function (e) {
    e.preventDefault();

    // Get all settings 'setting-name' attributes and their values
    var obj = {};
    $('.settings').each(function() {
        var name = $(this).attr('name');
        var value;

        if ($(this).attr('type') == 'checkbox') {
            value = $(this).is(':checked') ? 1 : 0;
        } else {
            value = $(this).val()
        }

        // If the parameter name contains dots, we create nested sub-objects accordingly
        if (name.includes('.')) {
            var parts = name.split('.');
            var ref = obj;

            for (var i = 0; i < parts.length; i++) {
                if (i === parts.length - 1) {
                    ref[parts[i]] = value;
                } else {
                    if (typeof ref[parts[i]] !== 'object' || ref[parts[i]] === null) {
                        ref[parts[i]] = {};
                    }
                    ref = ref[parts[i]];
                }
            }
        } else {
            obj[name] = value;
        }
    });

    ajaxRequest(
        // Controller:
        'hosts/settings',
        // Action:
        'edit',
        // Data:
        {
            settings: obj
        },
        // Print success alert:
        true,
        // Print error alert:
        true,
        // Reload container:
        ['hosts/list']
    );

    return false;
});