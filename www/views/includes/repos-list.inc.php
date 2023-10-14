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
        echo '<img src="/assets/icons/up.svg" class="hideGroup pointer float-right icon-lowopacity" group="' . $groupName . '" state="visible" />';
        echo "<h3>$groupName</h3>";

        /**
         *  Récupération de la liste des repos du groupe
         */
        $myrepoListing = new \Controllers\Repo\Listing();
        $reposList = $myrepoListing->listByGroup($groupName);

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
                    $printRepoName = true;
                    $printRepoDist = true;
                    $printRepoSection = true;
                    $printReleaseVersion = true;
                    $printEmptyLine = false;
                    $printDoubleEmptyLine = false;

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
                    if ($packageType == 'rpm') {
                        $releaseVersion = $repo['Releasever'];
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
                        $printRepoName = false;
                    }

                    if ($packageType == 'rpm') {
                        if ($name == $repoLastName and !empty($lastSnapId) and $snapId != $lastSnapId) {
                            $printEmptyLine = true;
                        }
                        if ($name == $repoLastName and !empty($releaseVersion) and $releaseVersion == $repoLastReleaseVersion) {
                            $printReleaseVersion = false;
                        }
                    }

                    if ($packageType == 'deb') {
                        if ($name == $repoLastName and !empty($repoLastDist) and $dist == $repoLastDist and !empty($repoLastSection) and $section == $repoLastSection) {
                            $printRepoDist = false;
                            $printRepoSection = false;
                        }
                        if ($name == $repoLastName and !empty($repoLastDist) and $repoLastDist != $dist) {
                            $printDoubleEmptyLine = true;
                        }
                        if ($name == $repoLastName and !empty($repoLastDist) and $repoLastDist == $dist and !empty($repoLastSection) and $section != $repoLastSection) {
                            $printEmptyLine = true;
                        }
                        if ($name == $repoLastName and !empty($repoLastDist) and $dist == $repoLastDist and !empty($repoLastSection) and $section == $repoLastSection and !empty($lastSnapId) and $snapId != $lastSnapId) {
                            $printEmptyLine = true;
                        }
                    }

                    /**
                     *  Si le type de paquet n'est pas le même que précédemment alors il faut afficher le nom du repo
                     */
                    if (!empty($lastPackageType) and $lastPackageType != $packageType and $repoLastName == $name) {
                        $printRepoName = true;
                        $printRepoDist = true;
                        $printRepoSection = true;
                        $printEmptyLine = true;
                    }

                    if ($printEmptyLine) {
                        echo '<div class="item-empty-line"></div>';
                    }
                    if ($printDoubleEmptyLine) {
                        echo '<div class="item-empty-line"></div>';
                        echo '<div class="item-empty-line"></div>';
                    } ?>

                    <div class="item-repo">
                        <?php
                        if ($printRepoName) : ?>
                            <div class="flex column-gap-8">
                                <span class="copy bold wordbreakall"><?= $name ?></span>
                                <span class="label-pkg-<?= $packageType ?>" title="This repository contains <?= $packageType ?> packages"><?= $packageType ?></span>
                            </div>
                            <?php
                        endif;

                        if ($packageType == 'deb') {
                            if ($printRepoDist or $printRepoSection) {
                                if ($printRepoDist) {
                                    echo '<span class="lowopacity-cst font-size-12" title="Distribution and section">' . ucfirst($dist) . ' ' . $section . '</span>';
                                }
                            }
                        }

                        if ($packageType == 'rpm') {
                            if ($printReleaseVersion) {
                                echo '<div class="lowopacity-cst font-size-12" title="Release version">Release ver. ' . $releaseVersion . '</div>';
                            }
                        } ?>
                    </div>

                    <div class="item-checkbox">
                        <?php
                        if ($snapId != $lastSnapId) {
                            /**
                             *  Print a warning icon if repo snapshot needs to be rebuilt
                             */
                            if (!empty($reconstruct)) {
                                if ($reconstruct == 'needed') {
                                    echo '<img class="icon" src="/assets/icons/warning.png" title="Repository snapshot content has been modified. You have to rebuild metadata." />';
                                }

                                /**
                                 *  Print a failed icon if repo snapshot rebuild has failed
                                 */
                                if ($reconstruct == 'failed') {
                                    echo '<img class="icon" src="/assets/icons/redcircle.png" title="Metadata building has failed." />';
                                }
                            }

                            /**
                             *  Print a warning icon if repo directory does not exist on the server
                             */
                            if ($packageType == 'rpm') {
                                if (!is_dir(REPOS_DIR . '/' . $dateFormatted . '_' . $name)) {
                                    echo '<img class="icon" src="/assets/icons/warning.png" title="This snapshot directory is missing on the server." />';
                                }
                            }
                            if ($packageType == 'deb') {
                                if (!is_dir(REPOS_DIR . '/' . $name . '/' . $dist . '/' . $dateFormatted . '_' . $section)) {
                                    echo '<img class="icon" src="/assets/icons/warning.png" title="This snapshot directory is missing on the server." />';
                                }
                            }
                        } ?>

                        <div>
                            <?php
                            /**
                             *  Checkbox are only printed for admin users
                             */
                            if (IS_ADMIN) :
                                /**
                                 *  On affiche la checkbox que lorsque le snapshot est différent du précédent et qu'il n'y a pas d'opération en cours sur le snapshot
                                 */
                                if ($snapId != $lastSnapId) :
                                    $myrepo = new \Controllers\Repo\Repo();
                                    if ($myrepo->snapOpIsRunning($snapId) === true) : ?>
                                        <img src="/assets/images/loading.gif" class="icon" title="An operation is running on this repository snaphot." />
                                    <?php else : ?>
                                        <input type="checkbox" class="icon-verylowopacity" name="checkbox-repo[]" repo-id="<?= $repoId ?>" snap-id="<?= $snapId ?>" <?php echo !empty($envId) ? 'env-id="' . $envId . '"' : ''; ?> repo-type="<?= $type ?>" title="Select and execute an action.">
                                        <?php
                                    endif;
                                endif;
                            endif ?>
                        </div>
                    </div>
   
                    <?php
                    /**
                     *  Get repo size in bytes
                     */
                    if ($packageType == 'rpm') {
                        $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $dateFormatted . '_' . $name);
                    }
                    if ($packageType == 'deb') {
                        $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $name . '/' . $dist . '/' . $dateFormatted . '_' . $section);
                    } ?>

                    <div class="item-snapshot">
                        <?php
                        if ($snapId != $lastSnapId) : ?>
                            <div class="item-date">
                                <a href="/browse/<?= $snapId ?>" title="<?= "Browse snapshot ($dateFormatted $time) content" ?>">
                                    <span><?= $dateFormatted ?></span>
                                </a>
                            </div>

                            <div class="item-info">
                                <span class="lowopacity-cst" title="Repository snapshot size"><?= \Controllers\Common::sizeFormat($repoSize) ?></span>
                                <span>
                                    <?php
                                    if ($type == "mirror") {
                                        echo '<img class="icon-np lowopacity-cst" src="/assets/icons/internet.svg" title="Type: mirror (source repository: ' . $source . ')&#10;Arch: ' . $arch . '" />';
                                    } elseif ($type == "local") {
                                        echo '<img class="icon-np lowopacity-cst" src="/assets/icons/pin.svg" title="Type: local&#10;Arch: ' . $arch . '" />';
                                    } else {
                                        echo '<img class="icon-np lowopacity-cst" src="/assets/icons/unknow.svg" title="Type: unknow" />';
                                    } ?>
                                </span>
                                
                                <span>
                                    <?php
                                    if ($signed == "yes") {
                                        echo '<img class="icon-np lowopacity-cst" src="/assets/icons/key.svg" title="Signed with GPG" />';
                                    } elseif ($signed == "no") {
                                        echo '<img class="icon-np" src="/assets/icons/key2.svg" title="Not signed with GPG" />';
                                    } else {
                                        echo '<img class="icon-np lowopacity-cst" src="/assets/icons/unknow.svg" title="GPG signature: unknow" />';
                                    } ?>
                                </span>
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
                    echo '</div>'; ?>

                    <div class="item-env" env-id="<?= $envId ?>">
                        <?php
                        if (!empty($env)) {
                            /**
                             *  Print env with a link to stats page if enabled
                             */
                            if (STATS_ENABLED == "true") {
                                echo '<a href="/stats/' . $envId . '" title="Visualize stats and metrics">';
                                echo \Controllers\Common::envtag($env, 'fit');
                                echo '</a>';
                            } else {
                                echo \Controllers\Common::envtag($env, 'fit');
                            }
                        } ?>
                    </div>

                    <div class="item-env-info" env-id="<?= $envId ?>">
                        <?php
                        if (!empty($env)) {
                            /**
                             *  Remove env icon
                             */
                            if (IS_ADMIN) {
                                echo '<img src="/assets/icons/delete.svg" class="delete-env-btn icon-lowopacity" title="Remove ' . $env . ' environment" repo-id="' . $repoId . '" snap-id="' . $snapId . '" env-id="' . $envId . '" env-name="' . $env . '" />';
                            }

                            /**
                             *  Repo installation icon
                             */
                            if ($packageType == 'rpm') {
                                echo '<img class="client-configuration-btn icon-lowopacity" package-type="rpm" repo="' . $name . '" env="' . $env . '" repo-dir-url="' . WWW_REPOS_DIR_URL . '" repo-conf-files-prefix="' . REPO_CONF_FILES_PREFIX . '" www-hostname="' . WWW_HOSTNAME . '" src="/assets/icons/terminal.svg" title="Show repo installation commands" />';
                            }
                            if ($packageType == 'deb') {
                                echo '<img class="client-configuration-btn icon-lowopacity" package-type="deb" repo="' . $name . '" dist="' . $dist . '" section="' . $section . '" env="' . $env . '" repo-dir-url="' . WWW_REPOS_DIR_URL . '" repo-conf-files-prefix="' . REPO_CONF_FILES_PREFIX . '" www-hostname="' . WWW_HOSTNAME . '" src="/assets/icons/terminal.svg" title="Show repo installation commands" />';
                            }
                        } ?>
                    </div>

                    <?php
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
                    if (!empty($releaseVersion)) {
                        $repoLastReleaseVersion = $releaseVersion;
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
            <span class="repo-action-btn btn-doGeneric" action="update" type="active-btn" title="Update selected snapshot(s)"><img class="icon" src="/assets/icons/update.svg" />Update</span>
            <span class="repo-action-btn btn-doGeneric" action="duplicate" type="active-btn" title="Duplicate select snapshot(s)"><img class="icon" src="/assets/icons/duplicate.svg" />Duplicate</span>
            <span class="repo-action-btn btn-doGeneric" action="env" type="active-btn" title="Point an environment to the selected snapshot(s)"><img class="icon" src="/assets/icons/link.svg" />Point an environment</span>
            <span class="repo-action-btn btn-doGeneric" action="reconstruct" type="active-btn" title="Rebuild selected snapshot(s) metadata"><img class="icon" src="/assets/icons/update.svg" />Rebuild</span>
            <span class="repo-action-btn btn-doConfirm" action="delete" type="active-btn" title="Delete selected snapshot(s)">Delete</span>
        </div>
    </div>
    <?php
endif ?>