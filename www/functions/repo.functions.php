<?php
/**
 *  Fonctions liées à l'affichage des listes de repos
 */

function group_by($key, $data)
{
    $result = array();

    foreach ($data as $val) {
        if (array_key_exists($key, $val)) {
            $result[$val[$key]][] = $val;
        } else {
            $result[""][] = $val;
        }
    }

    return $result;
}

function processList(array $reposList)
{
    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';
    $lastSnapId = '';

    foreach ($reposList as $repoArray) {
        // echo '<div class="repos-list-group-flex-div repos-list-type-' . strtolower(OS_FAMILY) . '" status="' . $repoStatus . '">';
        echo '<div class="repos-list-group-flex-div repos-list-type-' . strtolower(OS_FAMILY) . '">';

        foreach ($repoArray as $repo) {
            $repoId     = $repo['repoId'];
            $snapId     = $repo['snapId'];
            $repoName   = $repo['Name'];
            $repoSource = $repo['Source'];
            $repoStatus = $repo['Status'];
            $repoPackageType = $repo['Package_type'];
            if ($repoPackageType == 'deb') {
                $repoDist    = $repo['Dist'];
                $repoSection = $repo['Section'];
            }
            if (!empty($repo['envId'])) {
                $envId = $repo['envId'];
            } else {
                $envId = '';
            }
            if (!empty($repo['Env'])) {
                $repoEnv = $repo['Env'];
            } else {
                $repoEnv = '';
            }
            $repoDate   = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
            $repoTime   = $repo['Time'];
            $repoType   = $repo['Type'];
            $repoSigned = $repo['Signed'];
            if (!empty($repo['Description'])) {
                $repoDescription = $repo['Description'];
            } else {
                $repoDescription = '';
            }

            /**
             *  On transmets ces infos à la fonction printRepoLine qui va se charger d'afficher la ligne du repo
             */
            if ($repoPackageType == 'rpm') {
                printRepoLine(compact('repoId', 'snapId', 'envId', 'repoPackageType', 'repoName', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoStatus', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'lastSnapId'));
            }
            if ($repoPackageType == 'deb') {
                printRepoLine(compact('repoId', 'snapId', 'envId', 'repoPackageType', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoStatus', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection', 'lastSnapId'));
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
            if (!empty($snapId)) {
                $lastSnapId = $snapId;
            }
        }
        echo '</div>';
    }
}

/**
 *  Affiche la ligne d'un repo
 */
function printRepoLine($repoData = [])
{
    global $repoLastName;
    global $repoLastDist;
    global $repoLastSection;
    global $lastSnapId;

    /**
     *  Récupère les infos concernant le repo passées en argument
     */
    extract($repoData);

    $printRepoName = 'yes';
    $printRepoDist = 'yes';
    $printRepoSection = 'yes';
    $printRepoEnv = 'yes';
    $printEmptyLine = 'no';
    $mustReconstruct = 'no';

    if (OS_FAMILY == 'Redhat') {
        $repoPath = REPOS_DIR . '/' . $repoDate . '_' . $repoName;
    }
    if (OS_FAMILY == 'Debian') {
        $repoPath = REPOS_DIR . '/' . $repoName . '/' . $repoDist . '/' . $repoDate . '_' . $repoSection;
    }

    if (is_dir($repoPath . '/my_uploaded_packages') and !Models\Common::dirIsEmpty($repoPath . '/my_uploaded_packages')) {
        $mustReconstruct = 'yes';
    }

    /**
     *  Tests qui vont définir si on affiche une nouvelle fois le nom du repo/dist/section
     *  Utile pour ne pas afficher plusieurs fois l'information et alléger l'affichage
     */
    if ($repoLastName == $repoName) {
        $printRepoName = 'no';
    }

    if (OS_FAMILY == "Debian") {
        if ($repoName == $repoLastName and !empty($repoLastDist) and $repoDist == $repoLastDist and !empty($repoLastSection) and $repoSection == $repoLastSection) {
            $printRepoDist = 'no';
            $printRepoSection = 'no';
        }
        if ($repoName == $repoLastName and $repoLastDist != $repoDist) {
            $printEmptyLine = 'yes';
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
        if ($printRepoDist == 'yes' or $printRepoSection == 'yes') {
            echo '<div class="item-dist-section">';
                echo '<div class="item-dist-section-sub">';
            if ($printRepoDist == 'yes') {
                echo '<span class="item-dist">' . $repoDist . '</span>';
            }
            if ($printRepoSection == 'yes') {
                echo '<span class="item-section">❯ ' . $repoSection . '</span>';
            }
                echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="item-dist-section"></div>';
        }
    } ?>

    <?php

    /**
     *  Les checkbox sont affichées uniquement pour les utilisateurs administrateurs
     */

    if (Models\Common::isadmin()) { ?>
        <div class="item-checkbox">
            <?php
            /**
             *  On affiche la checkbox lorsque le snapshot est différent du précédent
             */
            if ($snapId != $lastSnapId) : ?>
                <input type="checkbox" class="icon-verylowopacity" name="checkbox-repo[]" repo-id="<?= $repoId ?>" snap-id="<?= $snapId ?>" <?php echo !empty($envId) ? 'env-id="' . $envId . '"' : ''; ?> repo-type="<?= $repoType ?>">
            <?php endif ?>
        </div>   
        <?php
    } else {
        echo '<div class="item-checkbox"></div>';
    }

    /**
     *  Affichage de la taille
     */
    if (PRINT_REPO_SIZE == "yes") {
        if ($repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") {
                $repoSize = exec("du -hs " . REPOS_DIR . "/${repoDate}_${repoName} | awk '{print $1}'");
            }
            if (OS_FAMILY == "Debian") {
                $repoSize = exec("du -hs " . REPOS_DIR . "/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
            }
        }
    }

    /**
     *  Affichage de la date
     */
    echo '<div class="item-snapshot">';
    if ($snapId != $lastSnapId) {
        echo '<div class="item-date" title="' . $repoDate . ' ' . $repoTime . '">';
            echo '<span>' . $repoDate . '</span>';
        echo '</div>';

        echo '<div class="item-info lowopacity">';
        if (PRINT_REPO_SIZE == "yes") {
            echo '<span>' . $repoSize . '</span>';
        }
        if ($mustReconstruct == 'yes') {
            echo '<img class="icon" src="ressources/icons/warning.png" title="Le repo contient des paquets qui n\'ont pas été intégré. Vous devez reconstruire le repo pour les intégrer." />';
        }
            /**
             *  Affichage de l'icone du type de repo (miroir ou local)
             */
        if (PRINT_REPO_TYPE == 'yes') {
            if ($repoType == "mirror") {
                echo "<img class=\"icon\" src=\"ressources/icons/world.png\" title=\"Type : miroir (source : $repoSource)\" />";
            } elseif ($repoType == "local") {
                echo '<img class="icon" src="ressources/icons/pin.png" title="Type : local" />';
            } else {
                echo '<img class="icon" src="ressources/icons/unknow.png" title="Type : inconnu" />';
            }
        }
            /**
             *  Affichage de l'icone de signature GPG du repo
             */
        if (PRINT_REPO_SIGNATURE == 'yes') {
            if ($repoSigned == "yes") {
                echo '<img class="icon" src="ressources/icons/key.png" title="Repo signé avec GPG" />';
            } elseif ($repoSigned == "no") {
                echo '<img class="icon" src="ressources/icons/key2.png" title="Repo non-signé avec GPG" />';
            } else {
                echo '<img class="icon" src="ressources/icons/unknow.png" title="Signature GPG : inconnue" />';
            }
        }
            /**
             *  Affichage de l'icone "explorer"
             */
        if (OS_FAMILY == "Redhat") {
            echo "<a href=\"explore.php?id=${snapId}\"><img class=\"icon\" src=\"ressources/icons/search.png\" title=\"Explorer le repo $repoName ($repoDate)\" /></a>";
        }
        if (OS_FAMILY == "Debian") {
            echo "<a href=\"explore.php?id=${snapId}\"><img class=\"icon\" src=\"ressources/icons/search.png\" title=\"Explorer la section ${repoSection} ($repoDate)\" /></a>";
        }
            echo '</div>';
    }
    echo '</div>';

    /**
     *  Affichage d'une flèche uniquement si un environnement pointe vers le snapshot
     */
    if ($snapId == $lastSnapId) {
        echo '<div class="item-arrow-up">';
    } else {
        echo '<div class="item-arrow">';
    }
    if (!empty($repoEnv)) {
        echo '<span></span>';
    }
    echo '</div>';

    /**
     *  Affichage de l'environnement pointant vers le snapshot si il y en a un
     */
    echo '<div class="item-env">';
    if (!empty($repoEnv)) {
        echo \Models\Common::envtag($repoEnv, 'fit');
    }
    echo '</div>';

    echo '<div class="item-env-info">';
    if (!empty($repoEnv)) {
        /**
         *  Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
         */
        if (OS_FAMILY == "Redhat") {
            echo "<img class=\"client-configuration-btn icon-lowopacity\" os_family=\"Redhat\" repo=\"$repoName\" env=\"$repoEnv\" repo_dir_url=\"" . WWW_REPOS_DIR_URL . "\" repo_conf_files_prefix=\"" . REPO_CONF_FILES_PREFIX . "\" www_hostname=\"" . WWW_HOSTNAME . "\" src=\"ressources/icons/code.png\" title=\"Afficher la configuration client\" />";
        }
        if (OS_FAMILY == "Debian") {
            echo "<img class=\"client-configuration-btn icon-lowopacity\" os_family=\"Debian\" repo=\"$repoName\" dist=\"$repoDist\" section=\"$repoSection\" env=\"$repoEnv\" repo_dir_url=\"" . WWW_REPOS_DIR_URL . "\" repo_conf_files_prefix=\"" . REPO_CONF_FILES_PREFIX . "\" www_hostname=\"" . WWW_HOSTNAME . "\" src=\"ressources/icons/code.png\" title=\"Afficher la configuration client\" />";
        }

        /**
         *  Affichage de l'icone "statistiques"
         */
        if (CRON_STATS_ENABLED == "yes" and $repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") {
                echo "<a href=\"stats.php?id=${envId}\"><img class=\"icon-lowopacity\" src=\"ressources/icons/stats.png\" title=\"Voir les stats du repo $repoName (${repoEnv})\" /></a>";
            }
            if (OS_FAMILY == "Debian") {
                echo "<a href=\"stats.php?id=${envId}\"><img class=\"icon-lowopacity\" src=\"ressources/icons/stats.png\" title=\"Voir les stats de la section $repoSection (${repoEnv})\" /></a>";
            }
        }
        /**
         *  Affichage de l'icone "warning" si le répertoire du repo n'existe plus sur le serveur
         */
        if ($repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") {
                if (!is_dir(REPOS_DIR . "/${repoDate}_${repoName}")) {
                    echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
                }
            }
            if (OS_FAMILY == "Debian") {
                if (!is_dir(REPOS_DIR . "/$repoName/$repoDist/${repoDate}_${repoSection}")) {
                    echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
                }
            }
        }
    }

        /**
         *  Icone suppression de l'environnement
         */
    if (!empty($repoEnv) and \Models\Common::isadmin()) {
        echo '<img src="ressources/icons/bin.png" class="delete-env-btn icon-lowopacity" title="Supprimer l\'environnement ' . $repoEnv . '" repo-id="' . $repoId . '" snap-id="' . $snapId . '" env-id="' . $envId . '" env-name="' . $repoEnv . '" />';
    }

    echo '</div>';

    /**
     *  Affichage de la description
     */
    echo '<div class="item-desc">';
    if (!empty($repoEnv)) {
        echo '<input type="text" class="repoDescriptionInput" env-id="' . $envId . '" placeholder="🖉" value="' . $repoDescription . '" />';
    }
    echo '</div>';
}
?>