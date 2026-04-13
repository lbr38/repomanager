<?php
use \Controllers\Utils\Generate\Html\Label; ?>

<div class="div-generic-blue margin-top-15">
    <div class="flex justify-space-between">
        <div>
            <p class="font-size-18 mediumopacity-cst">
                <?php
                if ($repo['Package_type'] == 'deb') : ?>
                    <?= $repo['Dist'] ?> ● <?= $repo['Section'] ?>
                    <?php
                endif;

                if ($repo['Package_type'] == 'rpm') : ?>
                    Release version <?= $repo['Releasever'] ?>
                    <?php
                endif ?>
            </p>

            <p class="note">The repository description</p>
        </div>

        <?php
        if ($repo['Type'] == 'local') {
            echo '<img src="/assets/icons/pin.svg" class="icon-np mediumopacity-cst" title="Local repository">';
        } elseif ($repo['Type'] == 'mirror') {
            echo '<img src="/assets/icons/internet.svg" class="icon-np mediumopacity-cst" title="Remote repository">';
        } ?>
    </div>

    <div class="flex flex-direction-column row-gap-20 margin-top-30">
        <?php
        foreach ($myrepoListing->listSnapshots($repo['repoId']) as $snapshot) : 
            // TODO debug
            // print_r($repo);
            // print_r($snapshot);

            // Generate repo relative path
            if ($repo['Package_type'] == 'rpm') {
                $repoRelativePath = 'rpm/' .$repo['Name'] . '/' . $repo['Releasever'] . '/' . $snapshot['Date'];
            }

            if ($repo['Package_type'] == 'deb') {
                $repoRelativePath = 'deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/' . $snapshot['Date'];
            }

            // Check if a task is running on the snapshot
            $taskRunning = $repoSnapshotController->taskRunning($snapshot['Id']);

            $envCount = !empty($snapshot['Environments']) ? count(explode(',', $snapshot['Environments'])) : 0;
            $zIndex = $envCount + 2; // +2 for date and size ?>

            <div class="snap-container">
                <div class="flex align-item-center">
                    <div class="snap-date" style="z-index: <?= $zIndex-- ?>">
                        <?php
                        /**
                         *  Checkbox are printed for all users
                         *  Admins can execute all actions
                         *  Regular users can execute actions only if they have the permission to do so (but they can at least 'Install' the repository)
                         */
                        // Print checkbox only if the snapshot is different from the previous one and there is no operation running on the snapshot
                        if ($taskRunning) : ?>
                            <img src="/assets/icons/loading.svg" class="icon-np" title="A task is running on this repository snaphot." />
                            <?php
                        else : ?>
                            <input type="checkbox" cid="<?= $repo['repoId'] . $snapshot['Id'] ?>" class="icon-lowopacity" name="checkbox-repo" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" repo-type="<?= $repo['Type'] ?>" group-id="<?= $groupId ?>" title="Select and execute an action.">
                            <?php
                        endif ?>
                        <span><?= $snapshot['Date'] ?></span>
                    </div>
                    <span class="snap-size" style="z-index: <?= $zIndex-- ?>" title="Repository snapshot size" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" repo-relative-path="<?= $repoRelativePath ?>">Calc.</span>
                </div>
                <?php
                if (!empty($snapshot['Environments'])) {
                    foreach (explode(',', $snapshot['Environments']) as $env) {
                        echo Label::envtag($env, null, 'snap-env', 'z-index: ' . $zIndex--);
                    }
                } ?>
            </div>


            <?php
        endforeach ?>
    </div>
</div>

<script>
$(document).ready(function() {
    myrepo.getSize();
    //myrepo.getLatestTaskStatus();
});
</script>