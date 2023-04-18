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
        echo '<img src="assets/icons/up.svg" class="hideGroup pointer float-right icon-lowopacity" group="' . $groupName . '" state="visible" />';
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
            $repoLastName = '';
            $repoLastDist = '';
            $repoLastSection = '';
            $repoLastEnv = '';
            $lastSnapId = '';
            $lastPackageType = '';

            foreach ($reposList as $repoArray) {
                echo '<div class="repos-list-group-flex-div">';
                foreach ($repoArray as $repo) {
                    $envId = '';
                    $env = '';
                    $description = '';
                    $printRepoName = 'yes';
                    $printRepoDist = 'yes';
                    $printRepoSection = 'yes';
                    $printRepoEnv = 'yes';
                    $printEmptyLine = 'no';

                    /**
                     *  Retrieving values from database
                     */
                    $repoId = $repo['repoId'];
                    $snapId = $repo['snapId'];
                    $name   = $repo['Name'];
                    $source = $repo['Source'];
                    $reconstruct = $repo['Reconstruct'];
                    $status      = $repo['Status'];
                    $packageType = $repo['Package_type'];
                    $dateFormatted = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
                    $time   = $repo['Time'];
                    $type   = $repo['Type'];
                    $signed = $repo['Signed'];
                    $arch   = $repo['Arch'];
                    if ($packageType == 'deb') {
                        $dist    = $repo['Dist'];
                        $section = $repo['Section'];
                    }
                    if (!empty($repo['envId'])) {
                        $envId = $repo['envId'];
                    }
                    if (!empty($repo['Env'])) {
                        $env = $repo['Env'];
                    }
                    if (!empty($repo['Description'])) {
                        $description = $repo['Description'];
                    }
                    if ($packageType == 'rpm') {
                        $repoPath = REPOS_DIR . '/' . $dateFormatted . '_' . $name;
                    }
                    if ($packageType == 'deb') {
                        $repoPath = REPOS_DIR . '/' . $name . '/' . $dist . '/' . $dateFormatted . '_' . $section;
                    }

                    /**
                     *  Tests qui vont définir si on affiche une nouvelle fois le nom du repo/dist/section
                     *  Utile pour ne pas afficher plusieurs fois l'information et alléger l'affichage
                     */
                    if ($repoLastName == $name) {
                        $printRepoName = 'no';
                    }

                    if ($packageType == 'rpm') {
                        if ($name == $repoLastName and !empty($lastSnapId) and $snapId != $lastSnapId) {
                            $printEmptyLine = 'yes';
                        }
                    }

                    if ($packageType == "deb") {
                        if ($name == $repoLastName and !empty($repoLastDist) and $dist == $repoLastDist and !empty($repoLastSection) and $section == $repoLastSection) {
                            $printRepoDist = 'no';
                            $printRepoSection = 'no';
                        }
                        if ($name == $repoLastName and !empty($repoLastDist) and $repoLastDist != $dist) {
                            $printEmptyLine = 'yes';
                        }
                        if ($name == $repoLastName and !empty($repoLastDist) and $repoLastDist == $dist and !empty($repoLastSection) and $section != $repoLastSection) {
                            $printEmptyLine = 'yes';
                        }
                        if ($name == $repoLastName and !empty($repoLastDist) and $dist == $repoLastDist and !empty($repoLastSection) and $section == $repoLastSection and !empty($lastSnapId) and $snapId != $lastSnapId) {
                            $printEmptyLine = 'yes';
                        }
                    }

                    /**
                     *  Si le type de paquet n'est pas le même que précédemment alors il faut afficher le nom du repo
                     */
                    if (!empty($lastPackageType) and $lastPackageType != $packageType and $repoLastName == $name) {
                        $printRepoName = 'yes';
                        $printRepoDist = 'yes';
                        $printRepoSection = 'yes';
                        $printEmptyLine = 'yes';
                    }

                    if ($printEmptyLine == 'yes') {
                        echo '<div class="item-empty-line"></div>';
                    }

                    /**
                     *  Nom du repo
                     */
                    echo '<div class="item-repo">';
                    if ($printRepoName == "yes") {
                        echo '<span>' . $name . '</span>';
                        echo '<div class="label-pkg-' . $packageType  . ' item-pkgtype" title="This repository contains ' . $packageType . ' packages"><img src="assets/icons/package.svg" class="icon-small" /><span>' . $packageType . '</span></div>';
                    }
                    echo '</div>';

                    /**
                     *  Nom de la distribution et de la section (Debian)
                     */
                    if ($packageType == "deb") {
                        if ($printRepoDist == 'yes' or $printRepoSection == 'yes') {
                            echo '<div class="item-dist-section">';
                                echo '<div class="item-dist-section-sub">';
                            if ($printRepoDist == 'yes') {
                                echo '<span class="item-dist">' . $dist . '</span>';
                            }
                            if ($printRepoSection == 'yes') {
                                echo '<span class="item-section">❯ ' . $section . '</span>';
                            }
                                echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<div class="item-dist-section"></div>';
                        }
                    } else {
                        echo '<div></div>';
                    } ?>
                   
                    <div class="item-checkbox">
                        <?php
                        /**
                         *  Checkbox are only printed for admin users
                         */
                        if (IS_ADMIN) :
                            /**
                             *  On affiche la checkbox que lorsque le snapshot est différent du précédent et qu'il n'y a pas d'opération en cours sur le snapshot
                             */
                            if ($snapId != $lastSnapId) :
                                if ($myrepo->snapOpIsRunning($snapId) === true) : ?>
                                    <img src="assets/images/loading.gif" class="icon" title="An operation is running on this repository snaphot." />
                                <?php else : ?>
                                    <input type="checkbox" class="icon-verylowopacity" name="checkbox-repo[]" repo-id="<?= $repoId ?>" snap-id="<?= $snapId ?>" <?php echo !empty($envId) ? 'env-id="' . $envId . '"' : ''; ?> repo-type="<?= $type ?>" title="Select and execute an action.">
                                    <?php
                                endif;
                            endif;
                        endif ?>
                    </div>
   
                    <?php
                    /**
                     *  Get repo size in bytes
                     */
                    if (PRINT_REPO_SIZE == "yes") {
                        if ($packageType == "rpm") {
                            $repoSize = \Controllers\Common::getDirectorySize(REPOS_DIR . '/' . $dateFormatted . '_' . $name);
                        }
                        if ($packageType == "deb") {
                            $repoSize = \Controllers\Common::getDirectorySize(REPOS_DIR . '/' . $name . '/' . $dist . '/' . $dateFormatted . '_' . $section);
                        }
                    } ?>

                    <div class="item-snapshot">
                        <?php
                        if ($snapId != $lastSnapId) : ?>
                            <div class="item-date" title="<?= "$dateFormatted $time" ?>">
                                <span><?= $dateFormatted ?></span>
                            </div>

                            <div class="item-info">
                                <?php
                                if (PRINT_REPO_SIZE == "yes") {
                                    /**
                                     *  Print repo size in the most suitable byte format
                                     */
                                    echo '<span class="lowopacity" title="Repository snapshot size">' . \Controllers\Common::sizeFormat($repoSize) . '</span>';
                                }

                                /**
                                 *  Affichage de l'icone du type de repo (miroir ou local)
                                 */
                                if (PRINT_REPO_TYPE == 'yes') {
                                    echo '<span>';
                                    if ($type == "mirror") {
                                        echo '<img class="icon-np lowopacity" src="assets/icons/internet.svg" title="Type: mirror (source repo: ' . $source . ')&#10;Arch: ' . $arch . '" />';
                                    } elseif ($type == "local") {
                                        echo '<img class="icon-np lowopacity" src="assets/icons/pin.svg" title="Type: local&#10;Arch: ' . $arch . '" />';
                                    } else {
                                        echo '<img class="icon-np lowopacity" src="assets/icons/unknow.svg" title="Type: unknow" />';
                                    }
                                    echo '</span>';
                                }

                                /**
                                 *  Affichage de l'icone de signature GPG du repo
                                 */
                                if (PRINT_REPO_SIGNATURE == 'yes') {
                                    echo '<span>';
                                    if ($signed == "yes") {
                                        echo '<img class="icon-np lowopacity" src="assets/icons/key.svg" title="Signed with GPG" />';
                                    } elseif ($signed == "no") {
                                        echo '<img class="icon-np" src="assets/icons/key2.svg" title="Not signed with GPG" />';
                                    } else {
                                        echo '<img class="icon-np lowopacity" src="assets/icons/unknow.svg" title="GPG signature: unknow" />';
                                    }
                                    echo '</span>';
                                }

                                /**
                                 *  Affichage de l'icone "explorer"
                                 */
                                echo '<span>';
                                if ($packageType == "rpm") {
                                    echo "<a href=\"/browse?id=${snapId}\"><img class=\"icon lowopacity\" src=\"assets/icons/search.svg\" title=\"Browse $name ($dateFormatted) snapshot\" /></a>";
                                }
                                if ($packageType == "deb") {
                                    echo "<a href=\"/browse?id=${snapId}\"><img class=\"icon lowopacity\" src=\"assets/icons/search.svg\" title=\"Browse $section ($dateFormatted) snapshot\" /></a>";
                                }
                                echo '</span>';

                                if (!empty($reconstruct)) {
                                    echo '<span>';
                                    if ($reconstruct == 'needed') {
                                        echo '<img class="icon" src="assets/icons/warning.png" title="This snapshot content has been modified. You have to rebuild metadata." />';
                                    }
                                    if ($reconstruct == 'failed') {
                                        echo '<img class="icon" src="assets/icons/redcircle.png" title="Metadata building has failed." />';
                                    }
                                    echo '</span>';
                                } ?>
                            </div>
                            <?php
                        endif ?>
                    </div>
                    
                    <?php
                    /**
                     *  Affichage d'une flèche uniquement si un environnement pointe vers le snapshot
                     */
                    if ($snapId == $lastSnapId) {
                        echo '<div class="item-arrow-up">';
                    } else {
                        echo '<div class="item-arrow">';
                    }
                    if (!empty($env)) {
                        echo '<span></span>';
                    }
                    echo '</div>';

                    /**
                     *  Affichage de l'environnement pointant vers le snapshot si il y en a un
                     */
                    echo '<div class="item-env">';
                    if (!empty($env)) {
                        echo \Controllers\Common::envtag($env, 'fit');
                    }
                    echo '</div>';

                    echo '<div class="item-env-info">';
                    if (!empty($env)) {
                        /**
                         *  Delete env icon
                         */
                        if (IS_ADMIN) {
                            echo '<img src="assets/icons/delete.svg" class="delete-env-btn icon-lowopacity" title="Remove ' . $env . ' environment" repo-id="' . $repoId . '" snap-id="' . $snapId . '" env-id="' . $envId . '" env-name="' . $env . '" />';
                        }

                        /**
                         *  Print repo conf icon
                         */
                        if ($packageType == "rpm") {
                            echo '<img class="client-configuration-btn icon-lowopacity" package-type="rpm" repo="' . $name . '" env="' . $env . '" repo-dir-url="' . WWW_REPOS_DIR_URL . '" repo-conf-files-prefix="' . REPO_CONF_FILES_PREFIX . '" www-hostname="' . WWW_HOSTNAME . '" src="assets/icons/terminal.svg" title="Show repo installation commands" />';
                        }
                        if ($packageType == "deb") {
                            echo '<img class="client-configuration-btn icon-lowopacity" package-type="deb" repo="' . $name . '" dist="' . $dist . '" section="' . $section . '" env="' . $env . '" repo-dir-url="' . WWW_REPOS_DIR_URL . '" repo-conf-files-prefix="' . REPO_CONF_FILES_PREFIX . '" www-hostname="' . WWW_HOSTNAME . '" src="assets/icons/terminal.svg" title="Show repo installation commands" />';
                        }

                        /**
                         *  Stats icon
                         */
                        if (STATS_ENABLED == "true") {
                            if ($packageType == "rpm") {
                                echo "<a href=\"/stats?id=${envId}\"><img class=\"icon-lowopacity\" src=\"assets/icons/stats.svg\" title=\"Visualize stats and metrics of $name ($env)\" /></a>";
                            }
                            if ($packageType == "deb") {
                                echo "<a href=\"/stats?id=${envId}\"><img class=\"icon-lowopacity\" src=\"assets/icons/stats.svg\" title=\"Visualize stats and metrics of $section ($env)\" /></a>";
                            }
                        }

                        /**
                         *  Print a warning icon if repo directory does not exist on the server
                         */
                        if ($packageType == "rpm") {
                            if (!is_dir(REPOS_DIR . '/' . $dateFormatted . '_' . $name)) {
                                echo '<img class="icon" src="assets/icons/warning.png" title="This snapshot directory is missing on the server." />';
                            }
                        }
                        if ($packageType == "deb") {
                            if (!is_dir(REPOS_DIR . '/' . $name . '/' . $dist . '/' . $dateFormatted . '_' . $section)) {
                                echo '<img class="icon" src="assets/icons/warning.png" title="This snapshot directory is missing on the server." />';
                            }
                        }
                    }

                    echo '</div>';

                    /**
                     *  Affichage de la description
                     */
                    echo '<div class="item-desc">';
                    if (!empty($env)) {
                        echo '<input type="text" class="repoDescriptionInput" env-id="' . $envId . '" placeholder="🖉 add a description" value="' . $description . '" />';
                    }
                    echo '</div>';

                    if (!empty($name)) {
                        $repoLastName = $name;
                    }
                    if (!empty($dist)) {
                        $repoLastDist = $dist;
                    }
                    if (!empty($section)) {
                        $repoLastSection = $section;
                    }
                    if (!empty($snapId)) {
                        $lastSnapId = $snapId;
                    }
                    if (!empty($packageType)) {
                        $lastPackageType = $packageType;
                    }
                }
                echo '</div>';
            }
        } else {
            echo '<span class="lowopacity-cst">(empty)</span>';
        }
        echo '</div>';
    }
}

/**
 *  Boutons d'actions
 */
if (IS_ADMIN) : ?>
    <div id="repo-actions-btn-container" class="action hide">
        <div>
            <button class="repo-action-btn btn-medium-green" action="update" type="active-btn" title="Update selected snapshot(s)"><img class="icon" src="assets/icons/update.svg" />Update</button>
            <button class="repo-action-btn btn-medium-green" action="duplicate" type="active-btn" title="Duplicate select snapshot(s)"><img class="icon" src="assets/icons/duplicate.svg" />Duplicate</button>
            <button class="repo-action-btn btn-medium-green" action="env" type="active-btn" title="Point an environment to the selected snapshot(s)"><img class="icon" src="assets/icons/link.svg" />Point an env.</button>
            <button class="repo-action-btn btn-medium-green" action="reconstruct" type="active-btn" title="Rebuild selected snapshot(s) metadata"><img class="icon" src="assets/icons/update.svg" />Rebuild</button>
            <button class="repo-action-btn btn-medium-red" action="delete" type="active-btn" title="Delete selected snapshot(s)"><img class="icon" src="assets/icons/delete.svg" />Delete</button>
        </div>
    </div>
    <?php
endif ?>