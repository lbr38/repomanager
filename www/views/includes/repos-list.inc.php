<?php
/**
 *  Retrieve all group names
 */
$mygroup = new \Controllers\Group('repo');
$groupsList = $mygroup->listAll(true);

/**
 *  Print groups and repos
 */
if (!empty($groupsList)) {
    foreach ($groupsList as $group) :
        /**
         *  Getting repos list of the group
         */
        $myrepoListing = new \Controllers\Repo\Listing();
        $reposList = $myrepoListing->listByGroup($group['Name']);

        /**
         *  Count repositories
         *  To have the exact number of repos, count by their repoId (to avoid duplicate repos)
         */
        $reposCount = count(array_unique(array_column($reposList, 'repoId')));

        /**
         *  Generate count message
         */
        if ($reposCount < 2) {
            $countMessage = $reposCount . ' repository';
        } else {
            $countMessage = $reposCount . ' repositories';
        } ?>

        <div class="repos-list-group div-generic-blue veil-on-reload" group="<?= $group['Name'] ?>">
            <div class="flex justify-space-between">
                <div>
                    <p class="font-size-16"><?= $group['Name'] ?></p>
                    <p class="lowopacity-cst"><?= $countMessage ?></p>
                </div>
                <img src="/assets/icons/up.svg" class="hideGroup pointer icon-lowopacity" group-id="<?= $group['Id'] ?>" state="visible" />
            </div>

            <?php
            /**
             *  If the group is empty, move to the next group
             */
            if (empty($reposList)) {
                // close div.repos-list-group:
                echo '</div>';
                continue;
            } ?>

            <!-- div only used to the show / hide group feature -->
            <div class="repo-list-group-container margin-top-20" group-id="<?= $group['Id'] ?>">
                <?php
                /**
                 *  Grouping repos by name
                 */
                $reposList = \Controllers\Common::groupBy("Name", $reposList);

                /**
                 *  Declaration of variables used to compare values between two repos
                 */
                $previousName = '';
                $previousDist = '';
                $previousSection = '';
                $previousEnv = '';
                $previousSnapId = '';
                $previousPackageType = '';

                /**
                 *  $envCounter will be used to count the number of env for the current repo
                 *  If the current env is the third to be print for the current repo, then print an empty line to let a space between the previous env
                 */
                $envCounter = 1;

                foreach ($reposList as $repoArray) : ?>
                    <div class="repos-list-group-flex-div" group-id="<?= $group['Id'] ?>" group="<?= $group['Name'] ?>">
                        <?php
                        foreach ($repoArray as $repo) :
                            /**
                             *  Retrieving values from database
                             */
                            $name           = $repo['Name'];
                            $dist           = $repo['Dist'];
                            $section        = $repo['Section'];
                            $releaseVersion = $repo['Releasever'];
                            $source         = $repo['Source'];
                            $rebuild        = $repo['Reconstruct'];
                            $status         = $repo['Status'];
                            $packageType    = $repo['Package_type'];
                            $dateFormatted  = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
                            $time           = $repo['Time'];
                            $type           = $repo['Type'];
                            $signed         = $repo['Signed'];
                            $arch           = $repo['Arch'];
                            $env            = $repo['Env'];
                            $description    = $repo['Description'];
                            $repoId         = $repo['repoId'];
                            $snapId         = $repo['snapId'];
                            $envId          = $repo['envId'];

                            /**
                             *  Conditional variables to print or not some informations
                             */
                            $printRepoName        = true;
                            $printRepoDist        = true;
                            $printRepoSection     = true;
                            $printReleaseVersion  = true;
                            $printEmptyLine       = false;
                            $printDoubleEmptyLine = false;

                            if ($packageType != $previousPackageType) {
                                $printRepoName       = true;
                                $printRepoDist       = true;
                                $printRepoSection    = true;
                                $printReleaseVersion = true;
                                $envCounter          = 1;
                            }

                            if ($name != $previousName) {
                                $printRepoName       = true;
                                $printRepoDist       = true;
                                $printRepoSection    = true;
                                $printReleaseVersion = true;
                                $envCounter          = 1;
                            }

                            if ($packageType == 'rpm') {
                                if ($name == $previousName) {
                                    $printRepoName = false;
                                }
                                if ($name == $previousName and $snapId != $previousSnapId) {
                                    $printEmptyLine = true;
                                    $envCounter = 1;
                                }
                                if ($name == $previousName and $releaseVersion == $previousReleaseVersion) {
                                    $printReleaseVersion = false;
                                }

                                /**
                                 *  Reset previous dist and section values to avoid some display bugs with deb repos having the same name as rpm repos
                                 */
                                $previousDist = '';
                                $previousSection = '';
                            }

                            if ($packageType == 'deb') {
                                if ($name == $previousName and $dist == $previousDist and $section == $previousSection) {
                                    $printRepoName    = false;
                                    $printRepoDist    = false;
                                    $printRepoSection = false;
                                }
                                if ($name == $previousName and $previousDist != $dist) {
                                    $printDoubleEmptyLine = true;
                                    $envCounter = 1;
                                }
                                if ($name == $previousName and $previousDist == $dist and $section != $previousSection) {
                                    $printDoubleEmptyLine = true;
                                    $envCounter = 1;
                                }
                                if ($name == $previousName and $dist == $previousDist and $section == $previousSection and $snapId != $previousSnapId) {
                                    $printEmptyLine = true;
                                    $envCounter = 1;
                                }
                                if ($previousPackageType == 'deb' and $packageType == 'deb' and $name == $previousName) {
                                    $printRepoName = false;
                                }
                            }

                            /**
                             *  If the current env is the third to be print for the current repo, then print an empty line before to let a space between the previous env
                             */
                            if ($envCounter >= 3) {
                                $printEmptyLine = true;
                            }

                            /**
                             *  Print empty line
                             */
                            if ($printEmptyLine) {
                                echo '<div class="item-empty-line"></div>';
                            }

                            /**
                             *  Print double empty line
                             */
                            if ($printDoubleEmptyLine) {
                                echo '<div class="item-empty-line"></div>';
                                echo '<div class="item-empty-line"></div>';
                            } ?>

                            <div class="item-repo" name="<?= $name ?>" dist="<?= $dist ?>" section="<?= $section ?>" releasever="<?= $releaseVersion ?>">
                                <?php
                                if ($printRepoName) : ?>
                                    <div class="flex align-item-center column-gap-8">
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
                                if ($snapId != $previousSnapId) {
                                    /**
                                     *  Print a warning icon if repo snapshot needs to be rebuild
                                     */
                                    if (!empty($rebuild)) {
                                        if ($rebuild == 'needed') {
                                            echo '<img class="icon" src="/assets/icons/warning.png" title="Repository snapshot content has been modified. You have to rebuild metadata." />';
                                        }

                                        /**
                                         *  Print a failed icon if repo snapshot rebuild has failed
                                         */
                                        if ($rebuild == 'failed') {
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
                                         *  Print checkbox only if the snapshot is different from the previous one and there is no operation running on the snapshot
                                         */
                                        if ($snapId != $previousSnapId) :
                                            $myrepo = new \Controllers\Repo\Repo();
                                            if ($myrepo->snapOpIsRunning($snapId) === true) : ?>
                                                <img src="/assets/images/loading.gif" class="icon" title="A task is running on this repository snaphot." />
                                                <?php
                                            else : ?>
                                                <input type="checkbox" class="icon-verylowopacity" name="checkbox-repo" repo-id="<?= $repoId ?>" snap-id="<?= $snapId ?>" <?php echo !empty($envId) ? 'env-id="' . $envId . '"' : ''; ?> repo-type="<?= $type ?>" title="Select and execute an action.">
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
                                if ($snapId != $previousSnapId) : ?>
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
                                            if ($signed == "true") {
                                                echo '<img class="icon-np lowopacity-cst" src="/assets/icons/key.svg" title="Signed with GPG" />';
                                            } elseif ($signed == "false") {
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
                             *  Print an arrow only if an environment points to the snapshot
                             */
                            if ($snapId == $previousSnapId) {
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

                                    $envCounter++;
                                } ?>
                            </div>

                            <div class="item-env-info" env-id="<?= $envId ?>">
                                <?php
                                if (!empty($env)) {
                                    /**
                                     *  Remove env icon
                                     */
                                    if (IS_ADMIN) {
                                        echo '<img src="/assets/icons/delete.svg" class="delete-env-btn icon-lowopacity" title="Remove ' . $env . ' environment" repo-id="' . $repoId . '" snap-id="' . $snapId . '" env-id="' . $envId . '" env="' . $env . '" />';
                                    }

                                    /**
                                     *  Repo installation icon
                                     */
                                    if ($packageType == 'rpm') {
                                        echo '<img class="client-configuration-btn icon-lowopacity" package-type="rpm" repo="' . $name . '" env="' . $env . '" repo-dir-url="' . WWW_REPOS_DIR_URL . '" repo-conf-files-prefix="' . REPO_CONF_FILES_PREFIX . '" www-hostname="' . WWW_HOSTNAME . '" src="/assets/icons/terminal.svg" title="Show repo installation commands" />';
                                    }
                                    if ($packageType == 'deb') {
                                        echo '<img class="client-configuration-btn icon-lowopacity" package-type="deb" repo="' . $name . '" dist="' . $dist . '" section="' . $section . '" env="' . $env . '" arch="' . $arch . '" repo-dir-url="' . WWW_REPOS_DIR_URL . '" repo-conf-files-prefix="' . REPO_CONF_FILES_PREFIX . '" www-hostname="' . WWW_HOSTNAME . '" src="/assets/icons/terminal.svg" title="Show repo installation commands" />';
                                    }
                                } ?>
                            </div>

                            <?php
                            /**
                             *  Description input
                             */
                            echo '<div class="item-desc">';
                            if (!empty($env)) {
                                echo '<input type="text" class="repoDescriptionInput" env-id="' . $envId . '" placeholder="🖉 add a description" value="' . $description . '" />';
                            }
                            echo '</div>';

                            $previousName = $name;

                            if (!empty($dist)) {
                                $previousDist = $dist;
                            }
                            if (!empty($section)) {
                                $previousSection = $section;
                            }
                            if (!empty($releaseVersion)) {
                                $previousReleaseVersion = $releaseVersion;
                            }
                            $previousSnapId = $snapId;
                            $previousPackageType = $packageType;
                        endforeach ?>
                    </div>
                    <?php
                endforeach ?>
            </div>
        </div>
        <?php
    endforeach;
}

/**
 *  Action buttons
 */
if (IS_ADMIN) : ?>
    <div id="repo-actions-btn-container" class="action hide">
        <div>
            <span class="repo-action-btn btn-doGeneric" action="update" type="active-btn" title="Update selected snapshot(s)"><img class="icon" src="/assets/icons/update.svg" />Update</span>
            <span class="repo-action-btn btn-doGeneric" action="duplicate" type="active-btn" title="Duplicate select snapshot(s)"><img class="icon" src="/assets/icons/duplicate.svg" />Duplicate</span>
            <span class="repo-action-btn btn-doGeneric" action="env" type="active-btn" title="Point an environment to the selected snapshot(s)"><img class="icon" src="/assets/icons/link.svg" />Point an environment</span>
            <span class="repo-action-btn btn-doGeneric" action="rebuild" type="active-btn" title="Rebuild selected snapshot(s) metadata"><img class="icon" src="/assets/icons/update.svg" />Rebuild</span>
            <span class="repo-action-btn btn-doConfirm" action="delete" type="active-btn" title="Delete selected snapshot(s)">Delete</span>
        </div>
    </div>
    <?php
endif ?>