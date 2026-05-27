<?php
use \Controllers\Utils\Generate\Html\Label;
use \Controllers\User\Permission\Repo as RepoPermission;

$canEditRepo = RepoPermission::allowedAction('edit');

// 'editable' classes only add the pointer cursor hint (and the click-to-add placeholder) when the user is allowed to edit
$descriptionClass = $canEditRepo ? 'repo-description-editable' : '';
if ($canEditRepo && empty($repo['Description'])) {
    $descriptionClass .= ' repo-description-empty';
}

$tagsClass = $canEditRepo ? 'repo-tags-editable' : '';

if ($repo['Package_type'] == 'deb') {
    $accent = 'red';
}

if ($repo['Package_type'] == 'rpm') {
    $accent = 'blue';
} ?>

<div class="repo-item-wrapper <?= !empty($expandAlone) ? 'repo-span-full' : '' ?> <?= !empty($userPreferences['repositories']['list']['expand']) ? 'height-100' : '' ?>" repo-id="<?= $repo['repoId'] ?>">
    <div class="div-generic-blue margin-0 repo-item accent-<?= $accent ?> overflowx-auto" data-name="<?= $repo['Name'] ?>" data-dist="<?= $repo['Dist'] ?? '' ?>" data-section="<?= $repo['Section'] ?? '' ?>" data-releasever="<?= $repo['Releasever'] ?? '' ?>" data-type="<?= $repo['Type'] ?>" data-package-type="<?= $repo['Package_type'] ?>" data-description="<?= htmlspecialchars($repo['Description'] ?? '') ?>" data-tags="<?= $repo['Tags'] ?? '' ?>">
        <div class="flex row-gap-15 column-gap-20 justify-space-between">
            <div class="flex flex-wrap align-item-center row-gap-15 column-gap-15">
                <p class="font-size-16 mediumopacity-cst">
                    <a href="/repository/<?= $repo['repoId'] ?>" class="flex align-item-center column-gap-5">
                        <?php
                        if ($repo['Package_type'] == 'deb') : ?>
                            <span title="Repository name" class="repo-name"><?= $repo['Name'] ?></span>
                            <span class="dot">●</span>
                            <span title="Distribution"><?= strtolower(DEB_DISTRIBUTIONS[$repo['Dist']] ?? $repo['Dist']) ?></span>
                            <span class="dot">●</span>
                            <span title="Component"><?= $repo['Section'] ?></span>
                            <?php
                        endif;

                        if ($repo['Package_type'] == 'rpm') : ?>
                            <span title="Repository name" class="repo-name"><?= $repo['Name'] ?></span>
                            <span class="dot">●</span>
                            <span title="Release version"><?= $repo['Releasever'] ?></span>
                            <?php
                        endif ?>
                    </a>
                </p>

                <?php
                if (RepoPermission::allowedAction('rename')) : ?>
                    <div class="repo-rename-btn" repo-id="<?= $repo['repoId'] ?>" title="Rename repository">
                        <img src="/assets/icons/edit.svg" class="icon-np icon-small" />
                        <span>Rename</span>
                    </div>
                    <?php
                endif;

                if ($canEditRepo) : ?>
                    <div class="repo-add-description-btn" repo-id="<?= $repo['repoId'] ?>" title="Add a description"<?= empty($repo['Description']) ? '' : ' style="display: none;"' ?>>
                        <img src="/assets/icons/plus.svg" class="icon-np icon-small" />
                        <span>Description</span>
                    </div>

                    <div class="repo-add-tags-btn" repo-id="<?= $repo['repoId'] ?>" title="Add tags"<?= empty($repo['Tags']) ? '' : ' style="display: none;"' ?>>
                        <img src="/assets/icons/plus.svg" class="icon-np icon-small" />
                        <span>Tags</span>
                    </div>
                    <?php
                endif ?>
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

        <div class="repo-description-container"<?= empty($repo['Description']) ? ' style="display: none;"' : '' ?>>
            <p class="note repo-description-input width-100 <?= $descriptionClass ?>" repo-id="<?= $repo['repoId'] ?>" env-id="<?= $repo['envId'] ?>" title="Double-click to edit the description"><?= $repo['Description'] ?></p>
        </div>

        <div class="repo-snapshots flex flex-1 flex-direction-column row-gap-10 margin-top-20 margin-bottom-5">
            <?php
            foreach ($repoListingController->listSnapshots($repo['repoId']) as $snapshot) :
                // Generate repo relative path
                if ($repo['Package_type'] == 'rpm') {
                    $repoRelativePath = 'rpm/' .$repo['Name'] . '/' . $repo['Releasever'] . '/' . $snapshot['Date'];
                }

                if ($repo['Package_type'] == 'deb') {
                    $repoRelativePath = 'deb/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '/' . $snapshot['Date'];
                }

                $date = DateTime::createFromFormat('Y-m-d', $snapshot['Date'])->format('d-m-Y');

                // Check if a task is running on the snapshot
                $taskRunning = $repoSnapshotController->taskRunning($snapshot['Id']); ?>

                <div class="snap-container grid-rfr-1-2 pointer" cid="<?= $repo['repoId'] . $snapshot['Id'] ?>" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" repo-type="<?= $repo['Type'] ?>" group-id="<?= $groupId ?>" title="Click to select">
                    <div class="flex align-item-center column-gap-20">
                        <?php
                        if ($taskRunning) : ?>
                            <img src="/assets/icons/loading.svg" class="icon-np" title="A task is running on this repository snaphot." />
                            <?php
                        else : ?>
                            <input type="checkbox" class="snap-checkbox-input" cid="<?= $repo['repoId'] . $snapshot['Id'] ?>" name="checkbox-repo" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" repo-type="<?= $repo['Type'] ?>" group-id="<?= $groupId ?>">
                            <?php
                        endif ?>

                        <div class="flex align-item-center column-gap-10" title="Snapshot created at <?= $date . ' ' . $snapshot['Time'] ?>. Click to browse content">
                            <img src="/assets/icons/calendar.svg" class="snap-icon icon-np lowopacity-cst" />
                            <a href="/snapshot/<?= $snapshot['Id'] ?>"><span class="snap-date lowopacity"><?= DateTime::createFromFormat('Y-m-d', $snapshot['Date'])->format('d-m-Y') ?></span></a>
                        </div>

                        <div class="snap-separator"></div>

                        <div class="flex flex-wrap column-gap-20 row-gap-10">
                            <div class="flex align-item-center column-gap-6" title="Repository snapshot size">
                                <img src="/assets/icons/package.svg" class="icon-medium icon-np mediumopacity-cst" />
                                <span class="snap-size mediumopacity-cst" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" repo-relative-path="<?= $repoRelativePath ?>"><?= $snapshot['Size_human'] ?? '?' ?></span>
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
                        if (!empty($snapshot['Environments'])) :
                            $envNames = explode(',', $snapshot['Environments']);
                            $envIds = explode(',', $snapshot['EnvironmentIds']);

                            foreach ($envNames as $index => $env) :
                                $envId = $envIds[$index] ?? '';
                                $protectedEnv = in_array($env, $protectedEnvs, true); ?>

                                <div class="snap-env-container<?= $protectedEnv ? ' nopointer' : '' ?>" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" env-id="<?= $envId ?>" env="<?= $env ?>"<?= $protectedEnv ? ' data-protected="true"' : '' ?>>
                                    <input type="checkbox" class="select-env-checkbox" name="env-checkbox" repo-id="<?= $repo['repoId'] ?>" snap-id="<?= $snapshot['Id'] ?>" env-id="<?= $envId ?>" env="<?= $env ?>"<?= $protectedEnv ? ' disabled' : '' ?>>
                                    <?= Label::envtag($env, 'snap-env') ?>
                                </div>
                                <?php
                            endforeach;
                        endif ?>
                    </div>
                </div>
                <?php
            endforeach; ?>
        </div>

        <?php
        if ($canEditRepo && empty($repo['Tags'])) {
            $tagsClass .= ' repo-tags-empty';
        } ?>

        <div class="repo-tags-container"<?= empty($repo['Tags']) ? ' style="display: none;"' : '' ?>>
            <div class="repo-tags-display flex align-item-center flex-wrap column-gap-10 <?= $tagsClass ?>" repo-id="<?= $repo['repoId'] ?>" data-tags="<?= $repo['Tags'] ?? '' ?>">
                <?php
                if (!empty($repo['Tags'])) :
                    foreach (explode(',', $repo['Tags']) as $tag) : ?>
                        <div class="flex align-item-center column-gap-5 mediumopacity repo-tag-item" title="Click to filter by <?= htmlspecialchars($tag) ?> tag. Double-click to edit tags." repo-id="<?= $repo['repoId'] ?>">
                            <img src="/assets/icons/tag.svg" class="icon-np icon-small" />
                            <p class="font-size-13"><?= htmlspecialchars($tag) ?></p>
                        </div>
                        <?php
                    endforeach;
                endif ?>
            </div>
        </div>
    </div>
</div>
