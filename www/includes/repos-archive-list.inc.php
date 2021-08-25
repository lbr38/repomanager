<h3>REPOS ARCHIVÉS</h3>
<table class="list-repos-archived">
<?php

// initialise des variables permettant de simplifier l'affichage dans la liste des repos
$repoLastName = '';
$repoLastDist = '';
$repoLastSection = '';
$listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos

echo '<thead>';
echo '<tr>';
echo '<td class="td-fit"></td>';
echo '<td>Repo</td>';
if ($OS_FAMILY == "Debian") {
echo '<td>Distribution</td>';
echo '<td>Section</td>';
}
echo '<td>Date</td>';
if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
echo '<td>Taille</td>';
}
echo '<td>Description</td>';
echo '</tr>';
echo '</thead>';

$repo = new Repo();
$reposList = $repo->listAll_archived();
    
if (!empty($reposList)) {
    foreach($reposList as $repo) {
        $repoName = $repo['Name'];
        $repoSource = $repo['Source'];
        if ($OS_FAMILY == "Debian") {
            $repoDist = $repo['Dist'];
            $repoSection = $repo['Section'];
        }
        $repoDate = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
        $repoDescription = $repo['Description'];
        $repoType = $repo['Type'];
        $repoSigned = $repo['Signed'];

        // On calcule la taille des repos seulement si souhaité (car cela peut être une grosse opération si le repo est gros) :
        if ($OS_FAMILY == "Redhat" AND $printRepoSize == "yes") {
            $repoSize = exec("du -hs ${REPOS_DIR}/archived_${repoDate}_${repoName} | awk '{print $1}'");
        }
        if ($OS_FAMILY == "Debian" AND $printRepoSize == "yes") {
            $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoDate}_${repoSection} | awk '{print $1}'");
        }
        // Affichage des données
        // on souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
        if ($alternateColors == "yes" AND $repoName !== $repoLastName) {
            if ($listColor == "color1") { $listColor = 'color2'; }
            elseif ($listColor == "color2") { $listColor = 'color1'; }
        }
        echo "<tr class=\"$listColor\">";
        echo '<td class="td-fit">';
        // Affichage de l'icone "corbeille" pour supprimer le repo
        if ($OS_FAMILY == "Redhat") { // si rpm on doit présicer repoEnv dans l'url
            echo "<a href=\"operation.php?action=deleteArchive&repoName=${repoName}&repoDate=".DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d')."\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo archivé ${repoName}\" /></a>";
        }
        if ($OS_FAMILY == "Debian") {
            echo "<a href=\"operation.php?action=deleteArchive&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoDate=".DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d')."\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section archivée ${repoSection}\" /></a>";
        }
        // Affichage de l'icone "remise en production du repo"
        if ($OS_FAMILY == "Redhat") { // si rpm on doit présicer repoEnv dans l'url
            echo "<a href=\"operation.php?action=restore&repoName=${repoName}&repoDate=".DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d')."&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"icons/arrow-up.png\" title=\"Remettre en production le repo archivé ${repoName} en date du ${repoDate}\" /></a>";
        }
        if ($OS_FAMILY == "Debian") {
            echo "<a href=\"operation.php?action=restore&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoDate=".DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d')."&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"icons/arrow-up.png\" title=\"Remettre en production la section archivée ${repoSection} en date du ${repoDate}\" /></a>";
        }
        echo '</td>';
        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
        if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
            echo '<td class="td-fit"></td>';
        } else {
            echo "<td class=\"td-fit\">$repoName</td>";
        }
        if ($OS_FAMILY == "Debian") {
            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
            if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
                echo '<td class="td-fit"></td>';
            } else {
                echo "<td class=\"td-fit\">$repoDist</td>";
            }
            // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
            if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
                echo '<td class="td-fit"></td>';
            } else {
                echo "<td class=\"td-fit\">$repoSection</td>";
            }
        }
        echo "<td>$repoDate</td>";
        if ($printRepoSize == "yes") {
            echo "<td>$repoSize</td>";
        }
        echo "<td title=\"${repoDescription}\">$repoDescription</td>"; // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
        echo '<td class="td-fit">';
        // Affichage de l'icone du type de repo (miroir ou local)
        if ($printRepoType == "yes") {
            if ($repoType == "mirror") {
                echo "<img class=\"icon-lowopacity\" src=\"icons/world.png\" title=\"Type : miroir ($repoSource)\" />";
            }
            if ($repoType == "local") {
                echo '<img class="icon-lowopacity" src="icons/pin.png" title="Type : local" />';
            }
        }
        // Affichage de l'icone de signature GPG du repo
        if ($printRepoSignature == "yes") {
            if ($repoSigned == "yes") {
                echo '<img class="icon-lowopacity" src="icons/key.png" title="Repo signé avec GPG" />';
            } elseif ($repoSigned == "no") {
                echo '<img class="icon-lowopacity" src="icons/key2.png" title="Repo non-signé avec GPG" />';
            } else {
                echo '<img class="icon-lowopacity" src="icons/unknow.png" title="Signature GPG : inconnue" />';
            }
        }
        // Affichage de l'icone "explorer"
        if ($OS_FAMILY == "Redhat") {
            echo "<a href=\"explore.php?repo=${repoName}&date=${repoDate}&state=archived\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer le repo $repoName archivé (${repoDate})\" /></a>";
        }
        if ($OS_FAMILY == "Debian") {
            echo "<a href=\"explore.php?repo=${repoName}&dist=${repoDist}&section=${repoSection}&date=${repoDate}&state=archived\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer la section archivée ${repoSection} (${repoDate})\" /></a>";
        }
        // Affichage de l'icone "warning" si le répertoire du repo n'existe plus sur le serveur
        if ($OS_FAMILY == "Redhat") {
            if (!is_dir("$REPOS_DIR/archived_${repoDate}_${repoName}")) {
                echo '<img class="icon-lowopacity" src="icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
            }
        }
        if ($OS_FAMILY == "Debian") {
            if (!is_dir("$REPOS_DIR/$repoName/$repoDist/archived_${repoDate}_${repoSection}")) {
                echo '<img class="icon" src="icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
            }
        }
        echo '</td>';
        echo '</tr>';
    }
    if (!empty($repoName)) { $repoLastName = $repoName; }
    if ($OS_FAMILY == "Debian") {
        if (!empty($repoDist)) { $repoLastDist = $repoDist; }
        if (!empty($repoSection)) { $repoLastSection = $repoSection; }
    }
}

unset($i, $j, $repoGroups, $groupName, $repoGroupList, $rows, $row, $rowData, $repoFullInformations, $repoName, $repoDist, $repoSection, $repoEnv, $repoDate, $repoDescription, $repoSize, $repoLastName, $repoLastDist, $repoLastSection, $repoLastEnv);  
?>
</table>