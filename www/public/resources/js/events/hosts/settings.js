/**
 *  Event: Edit hosts settings
 */
$(document).on('submit','form#hosts-settings',function (e) {
    e.preventDefault();

    var settings = {
        compliance: {
            thresholds: {
                count: $('input[name="compliance-threshold-count"]').val(),
                days: $('input[name="compliance-threshold-days"]').val(),
                'security-update': $('input[name="compliance-security-update"]').is(':checked') ? 1 : 0,
                'reboot-required': $('input[name="compliance-reboot-required"]').is(':checked') ? 1 : 0
            },
            exclusions: {
                hosts: $('select[name="compliance-exclude-hosts"]').val() || [],
                packages: $('select[name="compliance-exclude-packages"]').val() || []
            }
        }
    };

    ajaxRequest(
        // Controller:
        'hosts/settings',
        // Action:
        'edit',
        // Data:
        {
            settings: settings
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