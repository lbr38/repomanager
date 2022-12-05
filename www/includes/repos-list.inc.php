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
        echo '<div class="repos-list-group div-generic-blue" group="' . $groupName . '">';
            /**
             *  Bouton permettant de masquer le contenu de ce groupe
             */
            echo '<img src="resources/icons/down.svg" class="hideGroup pointer float-right icon-lowopacity" group="' . $groupName . '" />';
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
            echo '<span class="lowopacity">(empty)</span>';
        }
        echo '</div>';
    }
}

/**
 *  Boutons d'actions
 */
if (\Controllers\Common::isadmin()) : ?>
    <div id="repo-actions-btn-container" class="action hide">
        <button class="repo-action-btn btn-medium-green" action="update" type="active-btn" title="Update selected snapshot(s)"><img class="icon" src="resources/icons/update.svg" />Update</button>
        <button class="repo-action-btn btn-medium-green" action="duplicate" type="active-btn" title="Duplicate select snapshot(s)"><img class="icon" src="resources/icons/duplicate.svg" />Duplicate</button>
        <button class="repo-action-btn btn-medium-green" action="env" type="active-btn" title="Point an environment to the selected snapshot(s)"><img class="icon" src="resources/icons/link.svg" />Point an env.</button>
        <button class="repo-action-btn btn-medium-green" action="reconstruct" type="active-btn" title="Rebuild selected snapshot(s) metadata"><img class="icon" src="resources/icons/update.svg" />Rebuild</button>
        <button class="repo-action-btn btn-medium-red" action="delete" type="active-btn" title="Delete selected snapshot(s)"><img class="icon" src="resources/icons/bin.svg" />Delete</button>
    </div>
<?php endif ?>