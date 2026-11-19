/**
 *  Set a history filter (stored in a cookie for PHP to load the right data), go back to the first page and reload the table
 *  @param {string} name
 *  @param {string} value
 */
function setHistoryFilter(name, value)
{
    mycookie.set('tables/history/list/' + name, encodeURIComponent(value), 1);
    mycookie.delete('tables/history/list/offset');

    mytable.reload('history/list');
}

/**
 *  Event: filter by category or user
 */
$(document).on('change', '#history-category', function () {
    setHistoryFilter('category', $(this).val());
});

$(document).on('change', '#history-user', function () {
    setHistoryFilter('user', $(this).val());
});

/**
 *  Event: filter by status, clicking again on the active status removes the filter
 */
$(document).on('click', '.history-state-filter-btn', function () {
    var state = $(this).attr('state');

    if (mycookie.get('tables/history/list/state') == state) {
        state = '';
    }

    setHistoryFilter('state', state);
});

/**
 *  Event: search, the table is reloaded when the user stops typing
 */
var historySearchTimeout = null;

$(document).on('input', '#history-search', function () {
    var value = $(this).val().trim();

    clearTimeout(historySearchTimeout);

    historySearchTimeout = setTimeout(function () {
        setHistoryFilter('search', value);
    }, 400);
});

/**
 *  Event: show or hide the details of an action
 */
$(document).on('click', '.history-item', function () {
    var id = $(this).attr('history-id');

    $('.history-item-details[history-id="' + id + '"]').toggleClass('hide');
    $(this).toggleClass('history-item-expanded');
});
