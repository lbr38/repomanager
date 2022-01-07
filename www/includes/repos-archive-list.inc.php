<?php
    $listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos
    
    $repoStatus = 'archived';

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