<?php
/**
 *  Print groups and repos
 */
if (!empty($groupsList)) {
    foreach ($groupsList as $group) :
        /**
         *  Permissions
         *  If the user is not an admin, check if the group is in the user permissions
         */
        if (!IS_ADMIN) {
            // If 'all' is not in the user permissions, then it means the user has specific permissions and cannot view all groups
            if (!in_array('all', USER_PERMISSIONS['repositories']['view'])) {
                // Check if the current group Id is in the user permissions, if not then skip to the next group
                if (!in_array($group['Id'], USER_PERMISSIONS['repositories']['view']['groups'])) {
                    continue;
                }
            }
        }

        /**
         *  Getting repos list of the group
         */
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

        <div class="repos-list-group div-generic-blue veil-on-reload" group-id="<?= $group['Id'] ?>" group="<?= $group['Name'] ?>">
            <div class="flex justify-space-between">
                <div>
                    <p class="font-size-16"><?= $group['Name'] ?></p>
                    <p class="lowopacity-cst"><?= $countMessage ?></p>
                </div>
                <img src="/assets/icons/view.svg" class="hideGroup pointer icon-lowopacity" group-id="<?= $group['Id'] ?>" state="visible" title="Hide/Show group">
            </div>

            <div class="repos-list-group-select-all-btns mediumopacity pointer hide" group-id="<?= $group['Id'] ?>">
                <input type="checkbox" group-id="<?= $group['Id'] ?>"><p>Select latest snapshots</p>
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
                $previousName = null;
                $previousDist = null;
                $previousSection = null;
                $previousEnv = null;
                $previousSnapId = null;
                $previousPackageType = null;
                $previousReleaseVersion = null;

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
                            $date           = $repo['Date'];
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

                            if ($name == $previousName) {
                                $printRepoName = false;
                            }

                            if ($packageType != $previousPackageType) {
                                $printRepoName = true;
                                $envCounter    = 1;
                            }

                            if ($packageType == 'rpm') {
                                $snapshotPath = REPOS_DIR . '/rpm/' . $name . '/' . $releaseVersion . '/' . $date;

                                if ($name == $previousName and $snapId != $previousSnapId) {
                                    $printEmptyLine = true;
                                    $envCounter = 1;
                                }
                                if ($name == $previousName and $releaseVersion === $previousReleaseVersion) {
                                    $printReleaseVersion = false;
                                }
                                if ($name == $previousName and $releaseVersion !== $previousReleaseVersion) {
                                    $printDoubleEmptyLine = true;
                                    $envCounter = 1;
                                }

                                /**
                                 *  Reset previous dist and section values to avoid some display bugs with deb repos having the same name as rpm repos
                                 */
                                $previousDist = '';
                                $previousSection = '';
                            }

                            if ($packageType == 'deb') {
                                $snapshotPath = REPOS_DIR . '/deb/' . $name . '/' . $dist . '/' . $section . '/' . $date;

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
                             *  Print double empty line
                             */
                            if ($printDoubleEmptyLine) {
                                echo '<div class="item-empty-line"></div>';
                            } ?>

                            <div class="item-repo" name="<?= $name ?>" dist="<?= $dist ?>" section="<?= $section ?>" releasever="<?= $releaseVersion ?>">
                                <?php
                                if ($printRepoName) : ?>
                                    <div class="flex align-item-center column-gap-8">
                                        <span class="copy bold wordbreakall"><?= $name ?></span>
                                        <span class="label-pkg-<?= $packageType ?>" title="This repository contains <?= $packageType ?> packages"><?= strtoupper($packageType) ?></span>
                                    </div>
                                    <?php
                                endif;

                                if ($packageType == 'deb') {
                                    if ($printRepoDist or $printRepoSection) {
                                        if ($printRepoDist) {
                                            echo '<span class="lowopacity-cst font-size-13" title="Distribution and section">' . ucfirst($dist) . ' ' . $section . '</span>';
                                        }
                                    }
                                }

                                if ($packageType == 'rpm') {
                                    if ($printReleaseVersion) {
                                        echo '<div class="lowopacity-cst font-size-13" title="Release version">Release version ' . $releaseVersion . '</div>';
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
                                            echo '<img class="icon-np" src="/assets/icons/warning.svg" title="Repository snapshot content has been modified. You have to rebuild metadata." />';
                                        }

                                        /**
                                         *  Print a failed icon if repo snapshot rebuild has failed
                                         */
                                        if ($rebuild == 'failed') {
                                            echo '<img class="icon-np" src="/assets/icons/error.svg" title="Metadata building has failed." />';
                                        }
                                    }

                                    /**
                                     *  Print a warning icon if snapshot directory does not exist on the server
                                     */
                                    if ($packageType == 'rpm') {
                                        if (!is_dir($snapshotPath)) {
                                            echo '<img class="icon-np" src="/assets/icons/warning.svg" title="This snapshot directory is missing on the server." />';
                                        }
                                    }
                                    if ($packageType == 'deb') {
                                        if (!is_dir($snapshotPath)) {
                                            echo '<img class="icon-np" src="/assets/icons/warning.svg" title="This snapshot directory is missing on the server." />';
                                        }
                                    }
                                }

                                /**
                                 *  Checkbox are printed for all users
                                 *  Admins can execute all actions
                                 *  Regular users can execute actions only if they have the permission to do so (but they can at least 'Install' the repository)
                                 */
                                // Print checkbox only if the snapshot is different from the previous one and there is no operation running on the snapshot
                                if ($snapId != $previousSnapId) :
                                    if ($repoSnapshotController->taskRunning($snapId)) : ?>
                                        <img src="/assets/icons/loading.svg" class="icon-np" title="A task is running on this repository snaphot." />
                                        <?php
                                    else : ?>
                                        <input type="checkbox" cid="<?= $repoId . $snapId ?>" class="icon-lowopacity" name="checkbox-repo" repo-id="<?= $repoId ?>" snap-id="<?= $snapId ?>" <?php echo !empty($envId) ? 'env-id="' . $envId . '"' : ''; ?> env-name="<?= $env ?>" repo-type="<?= $type ?>" group-id="<?= $group['Id'] ?>" title="Select and execute an action.">
                                        <?php
                                    endif;
                                endif ?>
                            </div>
        
                            <?php
                            /**
                             *  Generate repo relative path
                             */
                            if ($packageType == 'rpm') {
                                $repoRelativePath = 'rpm/' .$name . '/' . $releaseVersion . '/' . $date;
                            }

                            if ($packageType == 'deb') {
                                $repoRelativePath = 'deb/' . $name . '/' . $dist . '/' . $section . '/' . $date;
                            } ?>

                            <div class="item-snapshot">
                                <?php
                                if ($snapId != $previousSnapId) : ?>
                                    <div class="item-date">
                                        <?php
                                        if (IS_ADMIN or in_array('browse', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) : ?>
                                            <a href="/browse/<?= $snapId ?>" title="<?= "Browse snapshot ($dateFormatted $time) content" ?>">
                                                <span><?= $dateFormatted ?></span>
                                            </a>
                                            <?php
                                        else : ?>
                                            <span><?= $dateFormatted ?></span>
                                            <?php
                                        endif ?>
                                    </div>

                                    <div class="item-info">
                                        <span>
                                            <?php
                                            if ($type == "mirror") {
                                                echo '<img class="icon-np lowopacity-cst" src="/assets/icons/internet.svg" title="Type: mirror (source repository: ' . $source . ')&#10;Arch: ' . $arch . '" />';
                                            }
                                            if ($type == "local") {
                                                echo '<img class="icon-np lowopacity-cst" src="/assets/icons/pin.svg" title="Type: local&#10;Arch: ' . $arch . '" />';
                                            } ?>
                                        </span>
                                        
                                        <span>
                                            <?php
                                            if ($signed == "true") {
                                                echo '<img class="icon-np lowopacity-cst" src="/assets/icons/key.svg" title="Signed with GPG" />';
                                            }
                                            if ($signed == "false") {
                                                echo '<img class="icon-np" src="/assets/icons/key2.svg" title="Not signed with GPG" />';
                                            } ?>
                                        </span>

                                        <span class="item-size lowopacity-cst" title="Repository snapshot size" repo-id="<?= $repoId ?>" snap-id="<?= $snapId ?>" repo-relative-path="<?= $repoRelativePath ?>">Calc.</span>
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
                                    if (STATS_ENABLED == "true" and (IS_ADMIN or in_array('view-stats', USER_PERMISSIONS['repositories']['allowed-actions']['repos']))) {
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
                                // Environment checkbox
                                if (!empty($env)) {
                                    // Print a warning icon if the env link is broken (target environment link does not exist)
                                    if ($packageType == 'rpm') {
                                        if (!is_link(REPOS_DIR . '/rpm/' . $name . '/' . $releaseVersion . '/' . $env)) {
                                            echo '<img class="icon-np" src="/assets/icons/warning.svg" title="This environment link is broken." />';
                                        }
                                    }

                                    if ($packageType == 'deb') {
                                        if (!is_link(REPOS_DIR . '/deb/' . $name . '/' . $dist . '/' . $section . '/' . $env)) {
                                            echo '<img class="icon-np" src="/assets/icons/warning.svg" title="This environment link is broken." />';
                                        }
                                    }

                                    // If the user is an admin or is a regular user with the 'removeEnv' permission
                                    if (IS_ADMIN or in_array('removeEnv', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) { ?>
                                        <input type="checkbox" cid="<?= $repoId . $snapId . $envId ?>" class="select-env-checkbox icon-lowopacity" name="env-checkbox" repo-id="<?= $repoId ?>" snap-id="<?= $snapId ?>" env-id="<?= $envId ?>" env="<?= $env ?>" title="Select environment">
                                        <?php
                                    }
                                } ?>
                            </div>

                            <?php
                            // Description input
                            echo '<div class="item-desc">';
                            if (!empty($env)) {
                                echo '<input type="text" class="repo-description-input" env-id="' . $envId . '" placeholder="🖉 add a description" value=\'' . htmlspecialchars_decode($description) . '\' />';
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
} ?>

<script>
$(document).ready(function() {
    getReposSize();
});
</script>