/**
 * Event: when the period (days) selection is changed
 */
$('#stats-days-select').on('change', function() {
    // Get selected value
    const days = $(this).val();

    // Destroy and recreate chart with new days value
    EChart.recreate('line', 'repo-access-chart', true, 15000, days);
});