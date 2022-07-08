<?php

/**
 *  Récupération de tous les noms de groupes
 */

$mygroup = new \Controllers\Group('repo');
$groupsList = $mygroup->listAllWithDefault();

/**
 *  On va afficher le tableau de repos seulement si la commande précédente a trouvé des groupes dans le fichier (résultat non vide)
 */
if (!empty($groupsList)) {
    foreach ($groupsList as $groupName) {
        echo '<div class="repos-list-group div-generic-gray" group="' . $groupName . '">';
            /**
             *  Bouton permettant de masquer le contenu de ce groupe
             */
            echo '<img src="resources/icons/chevron-circle-down.png" class="hideGroup pointer float-right icon-lowopacity" group="' . $groupName . '" />';
            echo "<h3>$groupName</h3>";

            /**
             *  Récupération de la liste des repos du groupe
             */
            $myrepo = new \Controllers\Repo();
            $reposList = $myrepo->listByGroup($groupName);

        if (!empty($reposList)) {
            $reposList = \Controllers\Common::groupBy("Name", $reposList);

            /**
             *  Traitement de la liste des repos
             */
            $myrepo->printRepoList($reposList);
            unset($myrepo);
        } else {
            echo '<span class="lowopacity">(vide)</span>';
        }
        echo '</div>';
    }
}

/**
 *  Boutons d'actions
 */
if (Controllers\Common::isadmin()) : ?>
    <div id="repo-actions-btn-container" class="action hide">
        <button class="repo-action-btn btn-medium-green" action="update" type="active-btn" title="Mettre à jour le(s) snapshot(s) sélectionné(s)"><img class="icon" src="resources/icons/update.png" />Mettre à jour</button>
        <button class="repo-action-btn btn-medium-blue" action="duplicate" type="active-btn" title="Dupliquer le(s) snapshot(s) sélectionné(s)"><img class="icon" src="resources/icons/duplicate.png" />Dupliquer</button>
        <button class="repo-action-btn btn-medium-blue" action="env" type="active-btn"><img class="icon" src="resources/icons/link.png" />Nouvel env.</button>
        <button class="repo-action-btn btn-medium-blue" action="reconstruct" type="active-btn" title="Reconstruire les métadonnées de(s) snapshot(s) sélectionné(s)"><img class="icon" src="resources/icons/update.png" />Reconstruire</button>
        <button class="repo-action-btn btn-medium-red" action="delete" type="active-btn" title="Supprimer le(s) snapshot(s) sélectionné(s)"><img class="icon" src="resources/icons/bin.png" />Supprimer</button>
    </div>
<?php endif ?>