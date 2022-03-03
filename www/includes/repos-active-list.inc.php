<?php

$repoStatus = 'active';

/**
 *  Récupération de tous les noms de groupes
 */
$mygroup = new Group('repo');
$groupsList = $mygroup->listAllWithDefault();

/**
 *  On va afficher le tableau de repos seulement si la commande précédente a trouvé des groupes dans le fichier (résultat non vide)
 */
if (!empty($groupsList)) {
    foreach($groupsList as $groupName) {
        echo '<div class="repos-list-group" group="'.$groupName.'">';
            /**
             *  Bouton permettant de masquer le contenu de ce groupe
             */
            echo '<img src="ressources/icons/chevron-circle-down.png" class="hideGroup pointer float-right icon-lowopacity" group="'.$groupName.'" />';
            /**
             *  On n'affiche pas le nom de groupe "Default"
             */
            echo "<h3>$groupName</h3>";
            /**
             *  Récupération de la liste des repos du groupe
             */
            $reposList = $mygroup->listRepos($groupName);

            if (!empty($reposList)) {
                $reposList = group_by("Name", $reposList);
                /**
                 *  Traitement de la liste des repos
                 */
                processList($reposList);
            } else {
                echo '<p>Il n\'y a aucun repo dans ce groupe</p>';
            }
        echo '</div>';
    }
}

/**
 *  Boutons d'actions
 */
if (Common::isadmin()) {
    echo '<div id="repo-actions-btn-container" class="action hide">';
        /**
         *  Bouton 'Mettre à jour'
         */
        if (OS_FAMILY == 'Redhat') echo '<button class="repo-action-btn btn-medium-green" action="update" type="active-btn" title="Mettre à jour le(s) repo(s) sélectionné(s)"><img class="icon" src="ressources/icons/update.png" />Mettre à jour</button>';
        if (OS_FAMILY == 'Debian') echo '<button class="repo-action-btn btn-medium-green" action="update" type="active-btn" title="Mettre à jour le(s) section(s) de repo(s) sélectionnée(s)"><img class="icon" src="ressources/icons/update.png" />Mettre à jour</button>';

        /**
         *  Bouton 'Dupliquer'
         */
        if (OS_FAMILY == 'Redhat') echo '<button class="repo-action-btn btn-medium-blue" action="duplicate" type="active-btn" title="Dupliquer le(s) repo(s) sélectionné(s)"><img class="icon" src="ressources/icons/duplicate.png" />Dupliquer</button>';
        if (OS_FAMILY == 'Debian') echo '<button class="repo-action-btn btn-medium-blue" action="duplicate" type="active-btn" title="Dupliquer le(s) section(s) de repo(s) sélectionnée(s)"><img class="icon" src="ressources/icons/duplicate.png" />Dupliquer</button>';        
        
        /**
         *  Bouton 'Nouvel env.'
         */
        echo '<button class="repo-action-btn btn-medium-blue" action="env" type="active-btn"><img class="icon" src="ressources/icons/link.png" />Nouvel env.</button>';

        /**
         *  Bouton 'Restaurer'
         */
        if (OS_FAMILY == 'Redhat') echo '<button class="repo-action-btn btn-medium-blue" action="restore" type="archived-btn" title="Restaurer le(s) repo(s) archivé(s) sélectionné(s)"><img class="icon" src="ressources/icons/arrow-circle-up.png" />Restaurer</button>';
        if (OS_FAMILY == 'Debian') echo '<button class="repo-action-btn btn-medium-blue" action="restore" type="archived-btn" title="Restaurer le(s) section(s) de repo(s) archivée(s) sélectionnée(s)"><img class="icon" src="ressources/icons/arrow-circle-up.png" />Restaurer</button>';

        /**
         *  Bouton 'Reconstruire'
         */
        if (OS_FAMILY == 'Redhat') echo '<button class="repo-action-btn btn-medium-blue" action="reconstruct" type="active-btn" title="Reconstruire les métadonnées de(s) repo(s) sélectionné(s)"><img class="icon" src="ressources/icons/update.png" />Reconstruire</button>';
        if (OS_FAMILY == 'Debian') echo '<button class="repo-action-btn btn-medium-blue" action="reconstruct" type="active-btn" title="Reconstruire les métadonnées de(s) section(s) de repo(s) sélectionnée(s)"><img class="icon" src="ressources/icons/update.png" />Reconstruire</button>';

        /**
         *  Bouton 'Supprimer'
         */
        if (OS_FAMILY == 'Redhat') echo '<button class="repo-action-btn btn-medium-red" action="delete" type="active-btn archived-btn" title="Supprimer le(s) repo(s) sélectionné(s)"><img class="icon" src="ressources/icons/bin.png" />Supprimer</button>';
        if (OS_FAMILY == 'Debian') echo '<button class="repo-action-btn btn-medium-red" action="delete" type="active-btn archived-btn" title="Supprimer le(s) section(s) de repo(s) sélectionnée(s)"><img class="icon" src="ressources/icons/bin.png" />Supprimer</button>';
    echo '</div>';
} ?>