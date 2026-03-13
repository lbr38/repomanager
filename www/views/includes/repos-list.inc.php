<?php
use \Controllers\User\Permission\Repo as RepoPermission;
use \Controllers\Utils\Generate\Html\Label;
use \Controllers\Utils\Array\Sort;

/**
 *  Print groups and repos
 */
if (!empty($groups)) {
    foreach ($groups as $groupId => $group) :
        if (!$group['show']) {
            continue;
        }

        include(ROOT . '/views/includes/containers/repos/includes-temp/group.inc.php');

        continue;







        

        /**
         *  Count repositories
         *  To have the exact number of repos, count by their repoId (to avoid duplicate repos)
         */
        // $reposCount = count($finalReposList[$group['Repos']]); ?>

        <div class="repos-list-group veil-on-reload" group-id="<?= 'toto' //$groupId ?>" group="<?= 'toto' //$group['Name'] ?>">
            <div class="flex justify-space-between">
                <div>
                    <p class="font-size-16"><?= $group ?></p>
                    <p class="lowopacity-cst"><?= $reposCount . ' repositor' . ($reposCount > 1 ? 'ies' : 'y') ?></p>
                </div>
                <img src="/assets/icons/view.svg" class="hide-repo-group pointer icon-lowopacity" group-id="<?= 'toto' //$group['Id'] ?>" state="visible" title="Hide/Show group">
            </div>

            <div class="repos-list-group-select-all-btns mediumopacity pointer hide" group-id="<?= 'toto' //$group['Id'] ?>">
                <input type="checkbox" group-id="<?= 'toto' //$group['Id'] ?>"><p>Select latest snapshots</p>
            </div>

            <!-- div only used to the show / hide group feature -->
            <div class="repo-list-group-container margin-top-20" group-id="<?= 'toto' //$group['Id'] ?>">
                <?php
                // Sort repos by name
                // $reposList = Sort::byKey('Name', $reposList);

                // Variables to keep the previous values in the loop and decide if we need to print or not some values (to avoid duplicates)
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

                foreach ($group['Repos'] as $key => $repo) : ?>
                    <!-- <div class="repos-list-group-flex-div" group-id="<?= 'toto' //$group['Id'] ?>" group="<?= 'toto' //$group['Name'] ?>"> -->
                    
                    <?php
                    // foreach ($repoArray as $repo) :
                    // Retrieve values from database
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

                    // Conditional variables to print or not some informations
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

                        // Reset previous dist and section values to avoid some display bugs with deb repos having the same name as rpm repos
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

                        // Reset previous release version value to avoid some display bugs with rpm repos having the same name as deb repos
                        $previousReleaseVersion = '';
                    }

                    // If the current env is the third to be print for the current repo, then print an empty line before to let a space between the previous env
                    if ($envCounter >= 3) {
                        $printEmptyLine = true;
                    }

                    if ($repo['Type'] == 'mirror') {
                        $typeIcon = 'internet';
                    }
                    if ($repo['Type'] == 'local') {
                        $typeIcon = 'pin';
                    }

                    // Check if a task is running on the snapshot
                    $taskRunning = $repoSnapshotController->taskRunning($snapId);

                    // Print double empty line
                    if ($printDoubleEmptyLine) {
                        echo '<div class="item-empty-line"></div>';
                    } ?>

                    <?php
                    if ($printRepoName) : ?>
                        <h1 class="copy"><?= $repo['Name'] ?></h1>
                        <?php
                    endif ?>

                        

























                    <div>
                        <?php
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
                        $previousPackageType = $packageType; ?>
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
    myrepo.getSize();
    myrepo.getLatestTaskStatus();
});
</script>