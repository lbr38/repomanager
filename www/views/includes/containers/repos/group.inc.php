<div class="repos-list-group veil-on-reload" group-id="<?= $groupId ?>" group="<?= $group['name'] ?>">
    <div class="group-header">
        <div class="flex align-item-center column-gap-10">
            <img src="/assets/icons/folder.svg" class="group-header-icon lowopacity-cst" />
            <span class="group-header-name hide-repo-group pointer" group-id="<?= $groupId ?>" state="visible" title="Collapse group <?= $group['name'] ?>"><?= $group['name'] ?></span>
            <span class="group-header-count"><?= $group['count'] ?></span>
        </div>

        <div class="flex align-item-center column-gap-15 row-gap-15">
            <div class="select-all-btn repos-list-group-select-latest-btns btn-fit-tr align-item-center column-gap-8 pointer hide" group-id="<?= $groupId ?>">
                <span>Select latest snapshots</span>
                <input type="checkbox" group-id="<?= $groupId ?>" aria-hidden="true" tabindex="-1">
            </div>

            <div class="select-all-btn repos-list-select-all-btns btn-fit-tr align-item-center column-gap-8 pointer hide" group-id="<?= $groupId ?>">
                <span>Select all snapshots</span>
                <input type="checkbox" group-id="<?= $groupId ?>" aria-hidden="true" tabindex="-1">
            </div>
        </div>
    </div>

    <div class="group-content">
        <?php
        $previousName = null;

        // Count how many repos share the same Name within this group, so we know
        // whether a repo is alone on its row (and can then be expanded on full width)
        $repoNameCounts = [];
        foreach ($group['repos'] as $repoId) {
            $groupRepo = array_filter($repos, function ($repo) use ($repoId) {
                return $repo['repoId'] == $repoId;
            });

            if (empty($groupRepo)) {
                continue;
            }

            $groupRepoName = array_values($groupRepo)[0]['Name'];
            $repoNameCounts[$groupRepoName] = ($repoNameCounts[$groupRepoName] ?? 0) + 1;
        }

        $repoNameIndex = [];

        foreach ($group['repos'] as $repoId) {
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

            // Track the position of this repo among the ones sharing the same Name
            $repoNameIndex[$repo['Name']] = ($repoNameIndex[$repo['Name']] ?? 0) + 1;

            $class = 'grid grid-rfr-1-2';
            $expandAlone = false;

            // If expand is enabled, expand the repo container if it's the only one on its row
            if ($userPreferences['repositories']['list']['expand']) {
                if ($repoNameCounts[$repo['Name']] == 1) {
                    // Only one repo with this Name: expand the whole wrapper on full width
                    $class = '';
                } elseif ($repoNameCounts[$repo['Name']] % 2 != 0 && $repoNameIndex[$repo['Name']] == $repoNameCounts[$repo['Name']]) {
                    // Odd number of repos with this Name: the last one ends up alone
                    // on its row, so make it span the full width of the grid
                    $expandAlone = true;
                }
            }

            if ($userPreferences['repositories']['list']['row-by-row']) {
                $class = 'grid';
            }

            if ($previousName != $repo['Name']) :
                // Close previous repo div if it's not the first repo
                if ($previousName != null) {
                    echo '</div>';
                } ?>
             
                <!-- Opening repo div -->
                <!-- <div class="<?= $class ?> repos-row row-gap-20 column-gap-30 margin-bottom-40"> -->
                <div class="<?= $class ?> repos-row row-gap-20 column-gap-30">
                <?php
            endif;

            include(ROOT . '/views/includes/containers/repos/repo.inc.php');

            $previousName = $repo['Name'];
        }

        // Close the last opened repos-row div, if any repo was rendered
        if ($previousName != null) {
            echo '</div>';
        } else { ?>
            <div class="empty-state">
                <p class="empty-state-title">No repository yet!</p>
            </div>
            <?php
        } ?>
    </div>
</div>
