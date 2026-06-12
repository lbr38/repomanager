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
            $hideName = false;
            $class = 'grid grid-rfr-1-2';

            // If expand is enable, expand the repo if it's the only one in the group
            if ($repoListSettings['expand']) {
                if (count($group['repos']) == 1) {
                    $class = '';
                }
            }

            if ($repoListSettings['one-line']) {
                $class = '';
            }

            // Find the repo in the $repos array using array_filter
            $repo = array_filter($repos, function ($repo) use ($repoId) {
                return $repo['repoId'] == $repoId;
            });

            // If the repo is not found, skip to the next one
            if (empty($repo)) {
                continue;
            }

            // Get the first element of the array (there should be only one element since repoId is unique)
            $repo = array_values($repo)[0];

            if ($previousName != $repo['Name']) :
                // Close previous repo div if it's not the first repo
                if ($previousName != null) {
                    echo '</div>';
                } ?>
             
                <!-- Opening repo div -->
                <div class="<?= $class ?> column-gap-40">
                <?php
            endif;

            include(ROOT . '/views/includes/containers/repos/repo.inc.php');

            $previousName = $repo['Name'];
        } ?>
    </div>
</div>
