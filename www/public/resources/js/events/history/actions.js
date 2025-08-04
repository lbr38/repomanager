$(document).on('change','#user-select',function () {
    var id = $(this).val();
    // Add a cookie with the selected user Id for the table to display the right data
    mycookie.set('tables/history/list/id', id, 1);

    // Then reload table
    mytable.reload('history/list');
});