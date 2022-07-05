$(document).ready(function () {
    /**
     *  Autorechargement des stats en temps réel
     */
    setInterval(function () {
        $("#refresh-me").load(" #refresh-me > *");
    }, 1000);

    /**
     *  Gestion des boutons de filtres sur le graphique principal
     */
    $(".repo-access-chart-filter-button").click(function () {
        /**
         *  Affichage d'une icone "chargement"
         */
        $('#repo-access-chart-div').append('<div class="chart-loading"><span>Chargement des données<img src="resources/images/loading.gif" class="icon" /></span></div>');

        /**
         *  Récupération de la valeur du filtre sélectionné (1week, 1month...)
         */
        var filter = $(this).attr('filter');

        /**
         *  Rappel de l'url en cours en précisant le filtre souhaité et en récupérant le canvas #repo-access-chart qui contiendra les nouvelles valeurs en fonction du filtre choisi
         */
        $('#repo-access-chart').load(window.location.href + '&repo_access_chart_filter=' + filter + ' #repo-access-chart', function () {
            /**
             *  On récupère alors les nouvelles valeurs :
             *  Pour les labels : dans l'attribut labels="" de #repo-access-chart-labels
             *  Pour les data   : dans l'attribut data="" de #repo-access-chart-data
             */
            var labels_str = $('#repo-access-chart').find('#repo-access-chart-labels').attr('labels').replace(/'/g, "");
            var data_str = $('#repo-access-chart').find('#repo-access-chart-data').attr('data');
            /**
             *  On split les valeurs précédemment récupérées en un array, le séparateur des données récupérées étant une virgule
             */
            var labels_array = labels_str.split(", ");
            var data_array = data_str.split(", ");

            /**
             *  On alimente le chart avec les nouvelles valeurs puis on l'actualise (update)
             */
            myRepoAccessChart.data.datasets[0].data = data_array;
            myRepoAccessChart.data.labels = labels_array;
            myRepoAccessChart.update();

            /**
             *  Retrait de l'icone de chargement
             */
            $(".chart-loading").remove();
        });
    });
});