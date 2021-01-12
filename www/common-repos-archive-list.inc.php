<h5>REPOS ARCHIVÉS</h5>
<table class="list-repos">
<?php
// Liste des repos sans filtre par groupe
if ($filterByGroups == "no") {
    // initialise des variables permettant de simplifier l'affichage dans la liste des repos
    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos

    echo "<thead>";
    echo "<tr>";
    echo "<td class=\"td-fit\"></td>";
    echo "<td>Nom</td>";
    if ($OS_TYPE == "deb") {
        echo "<td>Distribution</td>";
        echo "<td>Section</td>";
    }
    echo "<td>Date</td>";
    if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
        echo "<td>Taille</td>";
    }
    echo "<td>Description</td>";
    echo "</tr>";
    echo "</thead>";
    $repoFile = file_get_contents($REPO_ARCHIVE_FILE);
    $rows = explode("\n", $repoFile);
    foreach($rows as $row) {
        if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
            //get row data
            $rowData = explode(',', $row);
            if ($OS_TYPE == "rpm") {
              $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
              $repoDate = str_replace(['Date=', '"'], '', $rowData[2]);
              $repoDescription = str_replace(['Description=', '"'], '', $rowData[3]);
            }
            if ($OS_TYPE == "deb") {
              $repoName = str_replace(['Name=', '"'], '', $rowData[0]);
              $repoDist = str_replace(['Dist=', '"'], '', $rowData[2]);
              $repoSection = str_replace(['Section=', '"'], '', $rowData[3]);
              $repoDate = str_replace(['Date=', '"'], '', $rowData[4]);
              $repoDescription = str_replace(['Description=', '"'], '', $rowData[5]);
            }

            // On calcule la taille des repos seulement si souhaité (car cela peut être une grosse opération si le repo est gros) :
            if ($OS_TYPE == "rpm" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/99_old_version_${repoDate}_${repoName} | awk '{print $1}'");
            }
            if ($OS_TYPE == "deb" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/99_old_version_${repoDate}_${repoSection} | awk '{print $1}'");
            }
            // Affichage des données
            // on souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
            if ($alternateColors == "yes" AND $repoName !== $repoLastName) {
                if ($listColor == "color1") { $listColor = 'color2'; }
                elseif ($listColor == "color2") { $listColor = 'color1'; }
            }
            echo "<tr class=\"$listColor\">";
            echo "<td class=\"td-fit\">";
            // Affichage de l'icone "corbeille" pour supprimer le repo
            if ($OS_TYPE == "rpm") { // si rpm on doit présicer repoEnv dans l'url
                echo "<a href=\"traitement.php?actionId=deleteOldRepo&repoName=${repoName}&repoDate=${repoDate}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo archivé ${repoName}\" /></a>";
            }
            if ($OS_TYPE == "deb") {
                echo "<a href=\"traitement.php?actionId=deleteOldRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoDate=${repoDate}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section archivée ${repoSection}\" /></a>";
            }

            // Affichage de l'icone "remise en production du repo"
            if ($OS_TYPE == "rpm") { // si rpm on doit présicer repoEnv dans l'url
                echo "<a href=\"traitement.php?actionId=restoreOldRepo&repoName=${repoName}&repoDate=${repoDate}&repoDescription=${repoDescription}\"><img class=\"icon-lowopacity-red\" src=\"icons/arrow-up.png\" title=\"Remettre en production le repo archivé ${repoName} en date du ${repoDate}\" /></a>";
            }
            if ($OS_TYPE == "deb") {
                echo "<a href=\"traitement.php?actionId=restoreOldRepo&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoDate=${repoDate}&repoDescription=${repoDescription}\"><img class=\"icon-lowopacity-red\" src=\"icons/arrow-up.png\" title=\"Remettre en production la section archivée ${repoSection} en date du ${repoDate}\" /></a>";
            }
            echo "</td>";
            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
            if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
                echo "<td></td>";
            } else {
                echo "<td>$repoName</td>";
            }

            if ($OS_TYPE == "deb") {
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
                    echo "<td></td>";
                } else {
                    echo "<td>$repoDist</td>";
                }
                // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
                if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
                    echo "<td></td>";
                } else {
                    echo "<td>$repoSection</td>";
                }
            }

            echo "<td>$repoDate</td>";
            if ($printRepoSize == "yes") {
                echo "<td>$repoSize</td>";
            }
            echo "<td title=\"${repoDescription}\">$repoDescription</td>"; // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
            echo "</tr>";
        }

        if (!empty($repoName)) { $repoLastName = $repoName; }
        if ($OS_TYPE == "deb") {
            if (!empty($repoDist)) { $repoLastDist = $repoDist; }
            if (!empty($repoSection)) { $repoLastSection = $repoSection; }
        }
    }
}?>
</tbody>
</table>