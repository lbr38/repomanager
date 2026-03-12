/**
 *  Event: select accesses environment
 */
$(document).on('change', 'select#accesses-env', function () {
    const envs = $(this).val();

    // Add a cookie with the selected envs for the table to display the right data
    mycookie.set('chart/stats/accesses/envs', JSON.stringify(envs), 1);

    EChart.recreate('line', 'repo-accesses-chart');
});

/**
 *  Event: select access requests range
 */
$(document).on('change', 'div#access-requests-range', function () {
    const period = $(this).attr('value');

    // Delete offset cookie to reset pagination to the first page after reloading events list
    mycookie.delete('tables/stats/access/offset');

    // Add a cookie with the selected period for the table to display the right data
    mycookie.set('tables/stats/access/period', period, 1);

    // Then reload table
    mytable.reload('stats/access');
});

/**
 *  Event: select ip access environment
 */
$(document).on('change', 'select#access-requests-env', function () {
    const envs = $(this).val();

    // Delete offset cookie to reset pagination to the first page after reloading events list
    mycookie.delete('tables/stats/access/offset');

    // Add a cookie with the selected envs for the table to display the right data
    mycookie.set('tables/stats/access/envs', JSON.stringify(envs), 1);

    // Then reload table
    mytable.reload('stats/access');
});

/**
 *  Event: select ip access range
 */
$(document).on('change', 'div#ip-access-range', function () {
    const period = $(this).attr('value');

    // Delete offset cookie to reset pagination to the first page after reloading events list
    mycookie.delete('tables/stats/ip-access/offset');

    // Add a cookie with the selected period for the table to display the right data
    mycookie.set('tables/stats/ip-access/period', period, 1);

    // Then reload table
    mytable.reload('stats/ip-access');
});

/**
 *  Event: select ip access environment
 */
$(document).on('change', 'select#ip-access-env', function () {
    const envs = $(this).val();

    // Delete offset cookie to reset pagination to the first page after reloading events list
    mycookie.delete('tables/stats/ip-access/offset');

    // Add a cookie with the selected envs for the table to display the right data
    mycookie.set('tables/stats/ip-access/envs', JSON.stringify(envs), 1);

    // Then reload table
    mytable.reload('stats/ip-access');
});
