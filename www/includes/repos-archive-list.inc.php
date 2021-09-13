<h3>REPOS ARCHIVÉS</h3>
<table class="list-repos">

<?php
    $listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos
    
    $repoListType = 'archived';

    /**
     *  Affichage de l'en-tête du tableau
     */
    printHead();

    $myrepo = new Repo();
    $reposList = $myrepo->listAll_archived();
    unset($myrepo);

    if (!empty($reposList)) {
        /**
         *  Traitement de la liste des repos
        */
        processList($reposList);
    }
?>
</table>