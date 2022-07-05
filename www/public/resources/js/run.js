$(document).ready(function () {
    /**
     *  Autorechargement du journal et des opération en cours (panneau gauche et panneau droit)
     */
    setInterval(function () {
        $(".mainSectionRight").load(window.location.href + " .mainSectionRight > *");
        $(".mainSectionLeft").load(window.location.href + " .mainSectionLeft > *");
    }, 3000);

    /**
     *  Afficher toutes les opérations terminées
     */
    $(document).on('click','#print-all-op',function () {
        $(".hidden-op").show();        // On affiche les opérations masquées
        $("#print-all-op").hide();    // On masque le bouton "Afficher tout"

        // Création d'un cookie (expiration 15min)
        document.cookie = "printAllOp=yes;max-age=900;";
    });
    /**
     *  Afficher toutes les opérations récurrentes terminées
     */
    $(document).on('click','#print-all-regular-op',function () {
        $(".hidden-regular-op").show();        // On affiche les opérations masquées
        $("#print-all-regular-op").hide();    // On masque le bouton "Afficher tout"

        // Création d'un cookie (expiration 15min)
        document.cookie = "printAllRegularOp=yes;max-age=900;";
    });

    /**
     *  Afficher ou non tout le détail d'une opération
     */
    $(document).on('click','#displayFullLogs-yes',function () {
        document.cookie = "displayFullLogs=yes";
        $(".mainSectionLeft").load(" .mainSectionLeft > *");
    });

    $(document).on('click','#displayFullLogs-no',function () {
        document.cookie = "displayFullLogs=no";
        $(".mainSectionLeft").load(" .mainSectionLeft > *");
    });
});