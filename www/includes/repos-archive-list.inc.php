<?php
$repoStatus = 'archived';

$myrepo = new Repo();
$reposList = $myrepo->listAll_archived();

echo '<div class="repos-list-group div-generic-gray">';

    if (!empty($reposList)) {

        $reposList = group_by("Name", $reposList);

        /**
         *  Traitement de la liste des repos
        */
        processList($reposList);

    } else {
        echo '<span class="lowopacity">(vide)</span>';
    }
echo '</div>';
?>