<?php
$repoStatus = 'archived';

$myrepo = new Repo();
$reposList = $myrepo->listAll_archived();

echo '<div class="repos-list-group">';

    if (!empty($reposList)) {

        $reposList = group_by("Name", $reposList);

        /**
         *  Traitement de la liste des repos
        */
        processList($reposList);

    } else {
        echo '<p>Il n\'y a aucun repo archivé</p>';
    }
echo '</div>';
?>