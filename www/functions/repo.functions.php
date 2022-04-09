<?php
/**
 * 	Fonctions liées à l'affichage des listes de repos
 */

function group_by($key, $data) {
    $result = array();

    foreach($data as $val) {
        if(array_key_exists($key, $val)){
            $result[$val[$key]][] = $val;
        }else{
            $result[""][] = $val;
        }
    }

    return $result;
}

function processList(array $reposList) {
    global $repoStatus;

    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';

    foreach($reposList as $repoArray) {

        echo '<div class="repos-list-group-flex-div repos-list-type-'.strtolower(OS_FAMILY).'" status="'.$repoStatus.'">';

        foreach ($repoArray as $repo) {
            $repoId     = $repo['Id'];
            $repoName   = $repo['Name'];
            $repoSource = $repo['Source'];
            if (OS_FAMILY == "Debian") {
                $repoDist    = $repo['Dist'];
                $repoSection = $repo['Section'];
            }
            if ($repoStatus == 'active') {
                $repoEnv = $repo['Env'];
            }
            $repoDate        = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
            $repoTime        = $repo['Time'];
            $repoType        = $repo['Type'];
            $repoSigned      = $repo['Signed'];
            $repoDescription = $repo['Description'];

            /**
             *  On transmets ces infos à la fonction printRepoLine qui va se charger d'afficher la ligne du repo
             */
            if ($repoStatus == 'active') {
                if (OS_FAMILY == "Redhat") printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
                if (OS_FAMILY == "Debian") printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
            }
            if ($repoStatus == 'archived') {
                if (OS_FAMILY == "Redhat") printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
                if (OS_FAMILY == "Debian") printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
            }

            if (!empty($repoName)) {
                $repoLastName = $repoName;
            }
            if (!empty($repoDist)) {
                $repoLastDist = $repoDist;
            }
            if (!empty($repoSection)) {
                $repoLastSection = $repoSection;
            }
        }
        echo '</div>';
    }
}

/**
 *  Affiche la ligne d'un repo
 */
function printRepoLine($repoData = []) {
    global $repoLastName;
    global $repoLastDist;
    global $repoLastSection;
    global $repoStatus;

    /**
	 * 	Récupère les infos concernant le repo passées en argument
	 */
    extract($repoData);

    $printRepoName = 'yes';
    $printRepoDist = 'yes';
    $printRepoSection = 'yes';
    $printRepoEnv = 'yes';
    $is_updatable = 'yes';
    $printEmptyLine = 'no';
    $must_reconstruct = 'no';

    if ($repoStatus == 'active') {
        if (OS_FAMILY == 'Redhat') $repoPath = REPOS_DIR.'/'.$repoName.'_'.$repoEnv;
        if (OS_FAMILY == 'Debian') $repoPath = REPOS_DIR.'/'.$repoName.'/'.$repoDist.'/'.$repoSection.'_'.$repoEnv;
    }
    if ($repoStatus == 'archived') {
        if (OS_FAMILY == 'Redhat') $repoPath = REPOS_DIR.'/archived_'.$repoDate.'_'.$repoName;
        if (OS_FAMILY == 'Debian') $repoPath = REPOS_DIR.'/'.$repoName.'/'.$repoDist.'/archived_'.$repoDate.'_'.$repoSection;
    }

    if (is_dir($repoPath.'/my_uploaded_packages') AND !Common::dir_is_empty($repoPath.'/my_uploaded_packages')) {
        $must_reconstruct = 'yes';
    }

    $arrayContent = array();
    $line = array();

    /**
     *  Tests qui vont définir si on affiche une nouvelle fois le nom du repo/dist/section
     *  Utile pour ne pas afficher plusieurs fois l'information et alléger l'affichage
     */
    if ($repoLastName == $repoName) {
        $printRepoName = 'no';
    }

    if (OS_FAMILY == "Debian") {
        if ($repoName == $repoLastName AND !empty($repoLastDist) AND $repoDist == $repoLastDist) {
            $printRepoDist = 'no';
        }
        if ($repoName == $repoLastName AND !empty($repoLastDist) AND $repoDist == $repoLastDist AND !empty($repoLastSection) AND $repoSection == $repoLastSection) {
            $printRepoSection = 'no';
        }
        if ($repoName == $repoLastName AND $repoLastDist != $repoDist) {
            $printEmptyLine = 'yes';
        }
    }
    /**
     *  Défini si le repo peut être mis à jour ou non
     */
    if ($repoStatus == 'archived') {
        $printRepoEnv = 'no';
    } else {
        if ($repoEnv != DEFAULT_ENV) {
            $is_updatable = 'no';
        }
    }

    if ($printEmptyLine == 'yes') {
        echo '<div class="item-empty-line"></div>';
    }

    /**
     *  Nom du repo
     */
    echo '<div class="item-repo">';
        if ($printRepoName == "yes") {
            echo $repoName;
        }
    echo '</div>';

    /**
     *  Nom de la distribution et de la section (Debian)
     */
    if (OS_FAMILY == "Debian") {
        if ($printRepoDist == 'yes' OR $printRepoSection == 'yes') {
            echo '<div class="item-dist-section">';
                echo '<div class="item-dist-section-sub">';
                    if ($printRepoDist == 'yes') {
                        echo '<span class="item-dist">'.$repoDist.'</span>';
                    }
                    if ($printRepoSection == 'yes') {
                        echo '<span class="item-section">❯ '.$repoSection.'</span>';
                    }
                echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="item-dist-section"></div>';
        } 
    } ?>

    <?php if (Common::isadmin()) { 
        /**
         *  Les checkbox sont affichées uniquement pour les utilisateurs administrateurs
         */
        ?>
        <div class="item-checkbox">
            <input type="checkbox" name="checkbox-repo[]" repo-id="<?= $repoId ?>" is-updatable="<?= $is_updatable ?>" <?php if ($printRepoEnv == 'yes') echo 'repo-env="'.$repoEnv.'"';?> repo-status="<?= $repoStatus ?>" class="icon-verylowopacity">
        </div>   
<?php
    } else {
        echo '<div class="item-checkbox"></div>';
    }

    /**
     *  Affichage de l'environnement
     */
    echo '<div class="item-env">';
    if ($printRepoEnv == 'yes') {
        echo Common::envtag($repoEnv, 'fit');
    }
    echo '</div>';

    echo '<div class="item-arrow">';
    if ($repoStatus == 'active') {
        echo '⟶';
    }
    echo '</div>';

    /**
     *  Affichage de la date
     */
    echo '<div class="item-date" title="'.$repoDate.' '.$repoTime.'"><span>'.$repoDate.'</span></div>';

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
    }

    echo '<div class="item-info">';
        if (PRINT_REPO_SIZE == "yes") {
            echo '<span>'.$repoSize.'</span>';
        }
        if ($must_reconstruct == 'yes') {
            echo '<img class="icon" src="ressources/icons/warning.png" title="Le repo contient des paquets qui n\'ont pas été intégré. Vous devez reconstruire le repo pour les intégrer." />';
        }

        /**
         *  Affichage de l'icone du type de repo (miroir ou local)
         */
        if (PRINT_REPO_TYPE == 'yes') {
            if ($repoType == "mirror") {
                echo "<img class=\"icon-lowopacity\" src=\"ressources/icons/world.png\" title=\"Type : miroir (source : $repoSource)\" />";
            } elseif ($repoType == "local") {
                echo '<img class="icon-lowopacity" src="ressources/icons/pin.png" title="Type : local" />';
            } else {
                echo '<img class="icon-lowopacity" src="ressources/icons/unknow.png" title="Type : inconnu" />';                
            }
        }
        /**
         *  Affichage de l'icone de signature GPG du repo
         */
        if (PRINT_REPO_SIGNATURE == 'yes') {
            if ($repoSigned == "yes") {
                echo '<img class="icon-lowopacity" src="ressources/icons/key.png" title="Repo signé avec GPG" />';
            } elseif ($repoSigned == "no") {
                echo '<img class="icon-lowopacity" src="ressources/icons/key2.png" title="Repo non-signé avec GPG" />';
            } else {
                echo '<img class="icon-lowopacity" src="ressources/icons/unknow.png" title="Signature GPG : inconnue" />';
            }
        }
        /**
         *  Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
         */
        if ($repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") echo "<img class=\"client-configuration-btn icon-lowopacity\" os_family=\"Redhat\" repo=\"$repoName\" env=\"$repoEnv\" repo_dir_url=\"".WWW_REPOS_DIR_URL."\" repo_conf_files_prefix=\"".REPO_CONF_FILES_PREFIX."\" www_hostname=\"".WWW_HOSTNAME."\" src=\"ressources/icons/code.png\" title=\"Afficher la configuration client\" />";
            if (OS_FAMILY == "Debian") echo "<img class=\"client-configuration-btn icon-lowopacity\" os_family=\"Debian\" repo=\"$repoName\" dist=\"$repoDist\" section=\"$repoSection\" env=\"$repoEnv\" repo_dir_url=\"".WWW_REPOS_DIR_URL."\" repo_conf_files_prefix=\"".REPO_CONF_FILES_PREFIX."\" www_hostname=\"".WWW_HOSTNAME."\" src=\"ressources/icons/code.png\" title=\"Afficher la configuration client\" />";
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
                if (!is_dir(REPOS_DIR."/${repoDate}_${repoName}")) echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
            }
            if (OS_FAMILY == "Debian") {
                if (!is_dir(REPOS_DIR."/$repoName/$repoDist/${repoDate}_${repoSection}")) echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
            }
        }
        if ($repoStatus == 'archived') {
            if (OS_FAMILY == "Redhat") {
                if (!is_dir(REPOS_DIR."/archived_${repoDate}_${repoName}")) echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
            }
            if (OS_FAMILY == "Debian") {
                if (!is_dir(REPOS_DIR."/$repoName/$repoDist/archived_${repoDate}_${repoSection}")) echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
            }
        }
    echo '</div>';

    /**
     *  Affichage de la description
     */
    echo '<div class="item-desc"><input type="text" class="repoDescriptionInput" repo-id="'.$repoId.'" repo-status="'.$repoStatus.'" value="'.$repoDescription.'" /></div>';
}
?>