<?php
/**
 *  Count repositories
 *  To have the exact number of repos, count by their repoId (to avoid duplicate repos)
 */

// echo '<pre>';
// print_r($group);
// echo '</pre>';


?>

<div class="repos-list-group veil-on-reload" group-id="<?= $groupId ?>" group="<?= $group['name'] ?>">
    <div class="group-header">
        <div class="group-header-left">
            <img src="/assets/icons/folder.svg" class="group-header-icon lowopacity-cst" />
            <span class="group-header-name"><?= $group['name'] ?></span>
            <span class="group-header-count"><?= $group['count'] ?></span>
        </div>
        <div class="group-header-right">
            <img src="/assets/icons/view.svg" class="hide-repo-group pointer icon-lowopacity" group-id="<?= $groupId ?>" state="visible" title="Hide/Show group">
        </div>
    </div>

    <div class="group-content">
        <div class="repos-list-group-select-all-btns mediumopacity pointer hide" group-id="<?= $groupId ?>">
            <input type="checkbox" group-id="<?= $groupId ?>"><p>Select latest snapshots</p>
        </div>

        <?php
        $previousName = null;

        foreach ($group['repos'] as $repoId) {
            $repo = array_filter($repos, function ($repo) use ($repoId) {
                return $repo['repoId'] == $repoId;
            });

            // If the repo is not found, skip to the next one
            if (empty($repo)) {
                continue;
            }

            // Get the first element of the array (there should be only one element since repoId is unique)
            $repo = array_values($repo)[0];

            if ($previousName != $repo['Name']) {
                // Close previous repo div if it's not the first repo
                if ($previousName != null) {
                    echo '</div>';
                }
                echo '<div class="group-repo-name">';
                // echo '<img src="/assets/icons/package.svg" class="group-repo-name-icon" />';
                echo '<span>' . $repo['Name'] . '</span>';
                echo '</div>';
                echo '<div class="grid grid-rfr-1-2 column-gap-40">';
            }

            include(ROOT . '/views/includes/containers/repos/includes-temp/repo.inc.php');

            $previousName = $repo['Name'];
        } ?>
    </div>
</div>
