$(document).on('change','#user-select',function () {
    var id = $(this).val();

    console.log('User selected: ' + id);

    // Add a cookie with the selected user Id for the table to display the right data
    setCookie('tables/history/list/id', id, 1);

    // Then reload table
    reloadTable('history/list');
});