<?php

$listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos

$repoStatus = 'active';

/**
 *  Cas où on trie par groupes
 */
if ($filterByGroups == "yes") {
    /**
     *  Récupération de tous les noms de groupes
     */
    $mygroup = new Group();
    $groupsList = $mygroup->listAllWithDefault();

    /**
     *  On va afficher le tableau de repos seulement si la commande précédente a trouvé des groupes dans le fichier (résultat non vide)
     */
    if (!empty($groupsList)) {
        foreach($groupsList as $groupName) {
            $listColor = 'color1'; // réinitialise à color1 à chaque groupe
            echo "<tr colspan=\"100%\"><td><b>$groupName</b></td></tr>";

            /**
             *  Récupération de la liste des repos du groupe
             */
            $reposList = $mygroup->listRepos($groupName);
            if (!empty($reposList)) {
                /**
                 *  Affichage de l'en-tête du tableau
                 */
                printHead();

                /**
                 *  Traitement de la liste des repos
                 */
                processList($reposList);

            } else {
                echo '<tr><td colspan="100%">Il n\'y a aucun repo dans ce groupe</td></tr>';
            }
            echo '<tr><td><br></td></tr>'; // saut de ligne avant chaque nom de groupe
        }
    }
}

/**
 *  Cas où on ne trie pas par groupes
 */
if ($filterByGroups == "no") {
    /**
     *  Affichage de l'en-tête du tableau
     */
    printHead();

    $myrepo = new Repo();
    $reposList = $myrepo->listAll();
    unset($myrepo);

    if (!empty($reposList)) {
        /**
         *  Traitement de la liste des repos
        */
        processList($reposList);
    }
}
?>