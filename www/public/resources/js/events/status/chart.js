/**
 * Event: when the period (days) selection is changed
 */
$('#monitoring-days-select').on('change', function() {
    // Get selected value
    const days = $(this).val();

    // Destroy and recreate charts with new days value
    EChart.recreate('line', 'system-cpu-usage-chart', true, 15000, days);
    EChart.recreate('line', 'system-memory-usage-chart', true, 15000, days);
    EChart.recreate('line', 'system-disk-usage-chart', true, 15000, days);
});
