/**
 *  Event: Edit hosts settings
 */
$(document).on('submit','form#hosts-settings',function (e) {
    e.preventDefault();

    ajaxRequest(
        // Controller:
        'hosts/settings',
        // Action:
        'edit',
        // Data:
        {
            complianceThresholdCount: $('input[name="compliance-threshold-count"').val(),
            complianceThresholdDays: $('input[name="compliance-threshold-days"').val(),
            complianceRebootRequired: $('input[name="compliance-reboot-required"').is(':checked') ? 1 : 0
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