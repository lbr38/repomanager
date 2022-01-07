<?php
/**
 * 	Fonctions liées à l'affichage des listes de repos
 */

/**
 *  Affiche l'en-tête du tableau
 */
function printHead() {
    global $repoStatus;

    /**
     *  Affichage de l'entête (Repo, Distrib, Section, Env, Date...)
     */
    echo '<tr class="reposListHead">';
        //echo '<td class="td-30"></td>';
        echo '<td class="td-10"></td>';
        echo '<td class="td-30">Repo</td>';
        if (OS_FAMILY == "Debian") {
            if ($repoStatus == 'active') echo '<td class="td-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
            echo '<td class="td-30">Distribution</td>';
            if ($repoStatus == 'active') echo '<td class="td-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
            echo '<td class="td-30">Section</td>';
        }
        if ($repoStatus == 'active') { 
            echo '<td class="td-30">Env</td>'; // On affiche l'env uniquement pour les repos actifs
            echo '<td class="td-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
        }
        echo '<td class="td-30">Date</td>';
        if (PRINT_REPO_SIZE == "yes") { // On affiche la taille des repos seulement si souhaité
            echo '<td class="td-30">Taille</td>';
        }
        echo '<td class="td-desc">Description</td>';
        echo '<td class="td-fit"></td>';
    echo '</tr>';
}

function processList(array $reposList) {
    global $repoStatus;

    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';

    foreach($reposList as $repo) {
        $repoId = $repo['Id'];
        $repoName = $repo['Name'];
        $repoSource = $repo['Source'];
        if (OS_FAMILY == "Debian") {
            $repoDist = $repo['Dist'];
            $repoSection = $repo['Section'];
        }
        if ($repoStatus == 'active') {
            $repoEnv = $repo['Env'];
        }
        $repoDate = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
        $repoTime = $repo['Time'];
        $repoDescription = $repo['Description'];
        $repoType = $repo['Type'];
        $repoSigned = $repo['Signed'];
    
        /**
         *  On transmets ces infos à la fonction printRepo qui va se charger d'afficher la ligne du repo
         */
        if ($repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
            if (OS_FAMILY == "Debian") printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
        }
        if ($repoStatus == 'archived') {
            if (OS_FAMILY == "Redhat") printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
            if (OS_FAMILY == "Debian") printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
        }
        if (!empty($repoName)) { $repoLastName = $repoName; }
        if (OS_FAMILY == "Debian") {
            if (!empty($repoDist)) $repoLastDist = $repoDist;
            if (!empty($repoSection)) $repoLastSection = $repoSection;
        }
    }
}

/**
 *  Affiche la ligne d'un repo
 */
function printRepoLine($variables = []) {
    global $listColor;
    global $repoLastName;
    global $repoLastDist;
    global $repoLastSection;
    global $repoStatus;

	/**
	 * 	Récupère les infos concernant le repo passées en argument
	 */
    extract($variables);

    /**
     *  Affichage des données
     *  On souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
     */
    if (ALTERNATE_COLORS == "yes" AND $repoName !== $repoLastName) {
        if ($listColor == "color1") { $listColor = 'color2'; }
        elseif ($listColor == "color2") { $listColor = 'color1'; }
    }

    /**
     *  Affichage ou non d'une ligne séparatrice entre chaque repo/section
     */
    if (DIVIDING_LINE === "yes") {
        if (!empty($repoLastName) AND $repoName !== $repoLastName) {
            echo '<tr><td colspan="100%"><hr></td></tr>';
        }
    }

    echo "<tr class=\"$listColor\">";
        /**
         *  Affichage des icones d'opérations
         */
        echo '<td class="td-10">';
            if ($repoStatus == 'active') {
                /**
                 *  Affichage de l'icone "corbeille" pour supprimer le repo
                 *  Pour Redhat, on précise l'id du repo à supprimer
                 *  Pour Debian, on précise le nom du repo puisque celui-ci n'a pas d'id directement (ce sont les sections qui ont des id en BDD)
                 */
                if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=delete&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
                if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=delete&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";

                /**
                 *  Affichage de l'icone "dupliquer" pour dupliquer le repo
                 */
                if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=duplicate&id=${repoId}&repoGroup=ask&repoDescription=ask\"><img class=\"icon-lowopacity\" src=\"ressources/icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
                if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=duplicate&id=${repoId}&repoGroup=ask&repoDescription=ask\"><img class=\"icon-lowopacity\" src=\"ressources/icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";

                /**
                 *  Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
                 */
                if (OS_FAMILY == "Redhat") echo "<img class=\"client-configuration-button icon-lowopacity\" os_family=\"Redhat\" repo=\"$repoName\" env=\"$repoEnv\" repo_dir_url=\"".WWW_REPOS_DIR_URL."\" repo_conf_files_prefix=\"".REPO_CONF_FILES_PREFIX."\" www_hostname=\"".WWW_HOSTNAME."\" src=\"ressources/icons/code.png\" title=\"Afficher la configuration client\" />";
                if (OS_FAMILY == "Debian") echo "<img class=\"client-configuration-button icon-lowopacity\" os_family=\"Debian\" repo=\"$repoName\" dist=\"$repoDist\" section=\"$repoSection\" env=\"$repoEnv\" repo_dir_url=\"".WWW_REPOS_DIR_URL."\" repo_conf_files_prefix=\"".REPO_CONF_FILES_PREFIX."\" www_hostname=\"".WWW_HOSTNAME."\" src=\"ressources/icons/code.png\" title=\"Afficher la configuration client\" />";
                
                /**
                 *  Affichage de l'icone 'update' pour mettre à jour le repo/section. On affiche seulement si l'env du repo/section = DEFAULT_ENV et si il s'agit d'un miroir
                 */
                if ($repoType === "mirror" AND $repoEnv === DEFAULT_ENV) {
                    if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=update&id=${repoId}&repoGpgCheck=ask&repoGpgResign=ask\"><img class=\"icon-lowopacity\" src=\"ressources/icons/update.png\" title=\"Mettre à jour le repo ${repoName} (${repoEnv})\" /></a>";
                    if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=update&id=${repoId}&repoGpgCheck=ask&repoGpgResign=ask\"><img class=\"icon-lowopacity\" src=\"ressources/icons/update.png\" title=\"Mettre à jour la section ${repoSection} (${repoEnv})\" /></a>";
                }
            }
            if ($repoStatus == 'archived') {
                if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=deleteArchive&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer le repo archivé ${repoName}\" /></a>";
                if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=deleteArchive&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer la section archivée ${repoSection}\" /></a>";
                
                /**
                 *  Affichage de l'icone "remise en production du repo"
                 */
                if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=restore&id=${repoId}&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/arrow-circle-up.png\" title=\"Restaurer le repo archivé ${repoName} en date du ${repoDate}\" /></a>";
                if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=restore&id=${repoId}&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/arrow-circle-up.png\" title=\"Restaurer la section archivée ${repoSection} en date du ${repoDate}\" /></a>";
            }
        echo '</td>';

    /**
     *  Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent)
     */
    if (CONCATENATE_REPOS_NAME == "yes" AND $repoName === $repoLastName) {
        echo '<td class="td-30"></td>';
    } else {
        echo "<td class=\"td-30\">$repoName</td>";
    }
    if (OS_FAMILY == "Debian") {
        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
        if (CONCATENATE_REPOS_NAME == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
            if ($repoStatus == 'active') echo '<td class="td-fit"></td>';
            echo '<td class="td-30"></td>';
        } else {
            if ($repoStatus == 'active') echo "<td class=\"td-fit\"><a href=\"operation.php?action=deleteDist&id=${repoId}\"><img class=\"icon-verylowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
            echo "<td class=\"td-30\">$repoDist</td>";
        }

        if ($repoStatus == 'active') echo "<td class=\"td-fit\"><a href=\"operation.php?action=deleteSection&id=${repoId}\"><img class=\"icon-verylowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :    
        if (CONCATENATE_REPOS_NAME == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
            echo '<td class="td-30"></td>';
        } else {
            echo "<td class=\"td-30\">$repoSection</td>";
        }
    }

    /**
     *  Affichage de l'env en couleur
     *  On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
     */
    if ($repoStatus == 'active') {
        if (DEFAULT_ENV === LAST_ENV) { // Cas où il n'y a qu'un seul env
            echo "<td class=\"td-red-bckg td-30\"><span>$repoEnv</span></td>";
        } elseif ($repoEnv === DEFAULT_ENV) {
            echo "<td class=\"td-white-bckg td-30\"><span>$repoEnv</span></td>";
        } elseif ($repoEnv === LAST_ENV) {
            echo "<td class=\"td-red-bckg td-30\"><span>$repoEnv</span></td>";
        } else {
            echo "<td class=\"td-white-bckg td-30\"><span>$repoEnv</span></td>";
        }
        if (ENVS_TOTAL > 1) {
            /**
             *  Icone permettant d'ajouter un nouvel environnement, placée juste avant la date
             */           
            echo "<td class=\"td-fit\"><a href=\"operation.php?action=changeEnv&id=${repoId}&repoNewEnv=ask&repoDescription=ask\"><img class=\"icon-verylowopacity-red\" src=\"ressources/icons/link.png\" title=\"Faire pointer un nouvel environnement sur le repo $repoName du $repoDate\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
        }
    }

    /**
     *  Affichage de la date
     */
    echo "<td class=\"td-30\" title=\"$repoDate $repoTime\">$repoDate</td>";

    /**
     *  Affichage de la taille
     */
    if (PRINT_REPO_SIZE == "yes") {
        if ($repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") $repoSize = exec("du -hs ".REPOS_DIR."/${repoDate}_${repoName} | awk '{print $1}'");
            if (OS_FAMILY == "Debian") $repoSize = exec("du -hs ".REPOS_DIR."/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
        }

        if ($repoStatus == 'archived') {
            if (OS_FAMILY == "Redhat" AND PRINT_REPO_SIZE == "yes") $repoSize = exec("du -hs ".REPOS_DIR."/archived_${repoDate}_${repoName} | awk '{print $1}'");
            if (OS_FAMILY == "Debian" AND PRINT_REPO_SIZE == "yes") $repoSize = exec("du -hs ".REPOS_DIR."/${repoName}/${repoDist}/archived_${repoDate}_${repoSection} | awk '{print $1}'");
        }

        echo "<td class=\"td-30\">$repoSize</td>";
    }

    /**
     *  Affichage de la description
     */
    echo '<td class="td-desc">';
    echo '<input type="text" class="repoDescriptionInput invisibleInput" repo-id="'.$repoId.'" repo-status="'.$repoStatus.'" value="'.$repoDescription.'" />';
    echo '</td>';
    echo '<td class="td-fit">';
        /**
         *  Affichage de l'icone du type de repo (miroir ou local)
         */
        if (PRINT_REPO_TYPE == "yes") {
            if ($repoType == "mirror") {
                echo "<img class=\"icon-lowopacity\" src=\"ressources/icons/world.png\" title=\"Type : miroir ($repoSource)\" />";
            } elseif ($repoType == "local") {
                echo '<img class="icon-lowopacity" src="ressources/icons/pin.png" title="Type : local" />';
            } else {
                echo '<span title="Type : inconnu">?</span>';
            }
        }
        /**
         *  Affichage de l'icone de signature GPG du repo
         */
        if (PRINT_REPO_SIGNATURE == "yes") {
            if ($repoSigned == "yes") {
                echo '<img class="icon-lowopacity" src="ressources/icons/key.png" title="Repo signé avec GPG" />';
            } elseif ($repoSigned == "no") {
                echo '<img class="icon-lowopacity" src="ressources/icons/key2.png" title="Repo non-signé avec GPG" />';
            } else {
                echo '<img class="icon-lowopacity" src="ressources/icons/unknow.png" title="Signature GPG : inconnue" />';
            }
        }
        /**
         *  Affichage de l'icone "statistiques"
         */
        if (CRON_STATS_ENABLED == "yes" AND $repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") echo "<a href=\"stats.php?id=${repoId}\"><img class=\"icon-lowopacity\" src=\"ressources/icons/stats.png\" title=\"Voir les stats du repo $repoName (${repoEnv})\" /></a>";
            if (OS_FAMILY == "Debian") echo "<a href=\"stats.php?id=${repoId}\"><img class=\"icon-lowopacity\" src=\"ressources/icons/stats.png\" title=\"Voir les stats de la section $repoSection (${repoEnv})\" /></a>";
        }
        /**
         *  Affichage de l'icone "explorer"
         */
        if ($repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") echo "<a href=\"explore.php?id=${repoId}&state=active\"><img class=\"icon-lowopacity\" src=\"ressources/icons/search.png\" title=\"Explorer le repo $repoName (${repoEnv})\" /></a>";
            if (OS_FAMILY == "Debian") echo "<a href=\"explore.php?id=${repoId}&state=active\"><img class=\"icon-lowopacity\" src=\"ressources/icons/search.png\" title=\"Explorer la section ${repoSection} (${repoEnv})\" /></a>";
        }
        if ($repoStatus == 'archived') {
            if (OS_FAMILY == "Redhat") echo "<a href=\"explore.php?id=${repoId}&state=archived\"><img class=\"icon-lowopacity\" src=\"ressources/icons/search.png\" title=\"Explorer le repo $repoName archivé (${repoDate})\" /></a>";
            if (OS_FAMILY == "Debian") echo "<a href=\"explore.php?id=${repoId}&state=archived\"><img class=\"icon-lowopacity\" src=\"ressources/icons/search.png\" title=\"Explorer la section archivée ${repoSection} (${repoDate})\" /></a>";
        }
        /**
         *  Affichage de l'icone "warning" si le répertoire du repo n'existe plus sur le serveur
         */
        if ($repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") {
                if (!is_dir(REPOS_DIR."/${repoDate}_${repoName}")) {
                    echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
                }
            }
            if (OS_FAMILY == "Debian") {
                if (!is_dir(REPOS_DIR."/$repoName/$repoDist/${repoDate}_${repoSection}")) {
                    echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
                }
            }
        }
        if ($repoStatus == 'archived') {
            if (OS_FAMILY == "Redhat") {
                if (!is_dir(REPOS_DIR."/archived_${repoDate}_${repoName}")) {
                    echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
                }
            }
            if (OS_FAMILY == "Debian") {
                if (!is_dir(REPOS_DIR."/$repoName/$repoDist/archived_${repoDate}_${repoSection}")) {
                    echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
                }
            }
        }
        echo '</td>';
    echo '</tr>';
}
?>