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
    <div class="flex justify-space-between">
        <div>
            <p class="font-size-16"><?= $group['name'] ?></p>
            <p class="lowopacity-cst"><?= $group['count'] . ' repositor' . ($group['count']> 1 ? 'ies' : 'y') ?></p>
        </div>
        <img src="/assets/icons/view.svg" class="hide-repo-group pointer icon-lowopacity" group-id="<?= $groupId ?>" state="visible" title="Hide/Show group">
    </div>

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
            echo '<p class="margin-top-20 font-size-18">' . $repo['Name'] . '</p>';
        }

        include(ROOT . '/views/includes/containers/repos/includes-temp/repo.inc.php');

        $previousName = $repo['Name'];
    } ?>









</div>
