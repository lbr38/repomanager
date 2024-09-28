/**
 *  Event: select stats filter
 */
$(".repo-access-chart-filter-button").click(function () {
    /**
     *  Print a "loading" icon
     */
    $('#repo-access-chart-div').append('<div class="chart-loading"><span>Loading data<img src="/assets/icons/loading.svg" class="icon" /></span></div>');

    /**
     *  Retrieve the value of the selected filter (1week, 1month...)
     */
    var filter = $(this).attr('filter');

    /**
     *  Load the current url with the selected filter and retrieve the canvas #repo-access-chart which will contain the new values
     */
    $('#repo-access-chart').load(window.location.href + '?chartFilter=' + filter + ' #repo-access-chart', function () {
        /**
         *  Retrieve the new values:
         *  For the labels: in the attribute labels="" of #repo-access-chart-labels
         *  For the data  : in the attribute data="" of #repo-access-chart-data
         */
        var labels_str = $('#repo-access-chart').find('#repo-access-chart-labels').attr('labels').replace(/'/g, "");
        var data_str = $('#repo-access-chart').find('#repo-access-chart-data').attr('data');

        /**
         *  Split the values in an array, the separator being a comma
         */
        var labels_array = labels_str.split(", ");
        var data_array = data_str.split(", ");

        /**
         *  Feed the chart with the new values and then update it (reload it)
         */
        myRepoAccessChart.data.datasets[0].data = data_array;
        myRepoAccessChart.data.labels = labels_array;
        myRepoAccessChart.update();

        /**
         *  Remove the loading icon
         */
        $(".chart-loading").remove();
    });
});
