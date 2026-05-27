<?php
use \Controllers\Utils\Generate\Html\Label; ?>

<div class="div-generic-blue margin-top-15 repo-item repo-accent-<?= $repo['Package_type'] ?> overflowx-auto" data-name="<?= $repo['Name'] ?>" data-dist="<?= $repo['Dist'] ?? '' ?>" data-section="<?= $repo['Section'] ?? '' ?>" data-releasever="<?= $repo['Releasever'] ?? '' ?>" data-type="<?= $repo['Type'] ?>" data-package-type="<?= $repo['Package_type'] ?>" data-description="<?= htmlspecialchars($repo['Description'] ?? '') ?>" data-tags="<?= $repo['Tags'] ?? '' ?>">
    <div class="flex justify-space-between">
        <div>
            <p class="font-size-16 mediumopacity-cst">
                <a href="/stats/repo/<?= $repo['repoId'] ?>">
                    <?php
                    if ($repo['Package_type'] == 'deb') : ?>
                        <?= $repo['Dist'] ?> <span class="dot">●</span> <?= $repo['Section'] ?>
                        <?php
                    endif;

                    if ($repo['Package_type'] == 'rpm') : ?>
                        Release version <?= $repo['Releasever'] ?>
                        <?php
                    endif ?>
                </a>
            </p>            
        </div>

        <div class="flex align-item-start column-gap-15 mediumopacity-cst">
            <div class="flex align-item-center column-gap-5">
                <?php
                if ($repo['Type'] == 'local') {
                    echo '<img src="/assets/icons/pin.svg" class="icon-np icon-medium" title="This is a local repository." />';
                    echo '<span class="font-size-13">local</span>';
                } elseif ($repo['Type'] == 'mirror') {
                    echo '<img src="/assets/icons/internet.svg" class="icon-np icon-medium" title="This repository is a mirror of an external repository." />';
                    echo '<span class="font-size-13">mirror</span>';
                } ?>
            </div>

            <div class="flex align-item-center column-gap-5">
                <?php
                if ($repo['Package_type'] == 'deb') {
                    echo '<img src="/assets/icons/package.svg" class="icon-np icon-medium" title="This is a deb repository." />';
                    echo '<span class="font-size-13">deb</span>';
                } elseif ($repo['Package_type'] == 'rpm') {
                    echo '<img src="/assets/icons/package.svg" class="icon-np icon-medium" title="This is a rpm repository." />';
                    echo '<span class="font-size-13">rpm</span>';
                } ?>
            </div>
        </div>
    </div>

    <div class="repo-description-container">
        <p class="note repo-description-input width-100 <?= empty($repo['Description']) ? 'repo-description-empty' : '' ?>" repo-id="<?= $repo['repoId'] ?>" env-id="<?= $repo['envId'] ?>"><?= $repo['Description'] ?></p>
    </div>

    <div class="flex flex-direction-column row-gap-10 margin-top-20 overflowx-auto">
        <?php
        foreach ($myrepoListing->listSnapshots($repo['repoId']) as $snapshot) :
            // Generate repo relative path
            if ($repo['Package_type'] == 'rpm') {
                $repoRelativePath = 'rpm/' .$repo['Name'] . '/' . $repo['Releasever'] . '/' . $snapshot['Date'];
            }

            if ($repo['Package_type'] == 'deb') {
                $repoRelativePath = 'deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/' . $snapshot['Date'];
            }

            // Check if a task is running on the snapshot
            $taskRunning = $repoSnapshotController->taskRunning($snapshot['Id']); ?>

            <div class="snap-container grid-rfr-1-2 pointer" cid="<?= $repo['repoId'] . $snapshot['Id'] ?>" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" repo-type="<?= $repo['Type'] ?>" group-id="<?= $groupId ?>" title="Select and execute an action.">
                <div class="flex align-item-center column-gap-20">
                    <?php
                    if ($taskRunning) : ?>
                        <img src="/assets/icons/loading.svg" class="icon-np" title="A task is running on this repository snaphot." />
                        <?php
                    else : ?>
                        <input type="checkbox" class="snap-checkbox-input" cid="<?= $repo['repoId'] . $snapshot['Id'] ?>" name="checkbox-repo" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" repo-type="<?= $repo['Type'] ?>" group-id="<?= $groupId ?>">
                        <?php
                    endif ?>

                    <div class="flex align-item-center column-gap-10" title="Browse snapshot content">
                        <img src="/assets/icons/calendar.svg" class="snap-icon icon-np lowopacity-cst" />
                        <a href="/browse/<?= $snapshot['Id'] ?>"><span class="snap-date lowopacity"><?= DateTime::createFromFormat('Y-m-d', $snapshot['Date'])->format('d-m-Y') ?></span></a>
                    </div>

                    <div class="snap-separator"></div>

                    <div class="flex flex-wrap column-gap-20 row-gap-10">
                        <div class="flex align-item-center column-gap-6" title="Repository snapshot size">
                            <img src="/assets/icons/package.svg" class="icon-medium icon-np mediumopacity-cst" />
                            <span class="snap-size mediumopacity-cst" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" repo-relative-path="<?= $repoRelativePath ?>">Calc.</span>
                        </div>

                        <div class="flex align-item-center column-gap-6">
                            <?php
                            if ($snapshot['Signed'] == 'true') : ?>
                                <img src="/assets/icons/check.svg" class="icon-medium icon-np" />
                                <span class="snap-signed mediumopacity-cst" title="This snapshot is signed with GPG">Signed</span>
                                <?php
                            else : ?>
                                <img src="/assets/icons/error.svg" class="icon-medium icon-np" />
                                <span class="snap-signed mediumopacity-cst" title="This snapshot is not signed with GPG">Unsigned</span>
                                <?php
                            endif ?>
                        </div>

                        <div class="flex align-item-center column-gap-10">
                            <?php
                            if ($snapshot['Reconstruct'] == 'needed') : ?>
                                <img src="/assets/icons/warning.svg" class="icon-np" title="Snapshot content has been modified. Metadata rebuild is needed." />
                                <?php
                            endif ?>
                        </div>
                    </div>
                </div>

                <div class="snap-envs">
                    <?php
                    if (!empty($snapshot['Environments'])) {
                        $envNames = explode(',', $snapshot['Environments']);
                        $envIds = explode(',', $snapshot['EnvironmentIds']);

                        foreach ($envNames as $index => $env) {
                            $envId = $envIds[$index] ?? ''; ?>
                            <div class="snap-env-container" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" env-id="<?= $envId ?>" env="<?= $env ?>">
                                <input type="checkbox" class="select-env-checkbox" name="env-checkbox" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" env-id="<?= $envId ?>" env="<?= $env ?>">
                                <?= Label::envtag($env, null, 'snap-env') ?>
                            </div>
                            <?php
                        }
                    } ?>
                </div>
            </div>
            <?php
        endforeach ?>

        <div class="flex align-item-center column-gap-5">
            <?php
            if (!empty($repo['Tags'])) :
                foreach (explode(',', $repo['Tags']) as $tag) {
                    echo '<p class="mediumopacity-cst font-size-13">#' . $tag . '</p>';
                }
            endif ?>
        </div>
    </div>
</div>
