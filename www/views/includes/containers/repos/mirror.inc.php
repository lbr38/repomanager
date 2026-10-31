<?php
use \Controllers\Mirror\Mirror;
use \Controllers\Utils\Convert;
use \Controllers\Utils\Generate\Html\Label;
use \Controllers\User\Permission\Repo as RepoPermission;

$accent = $mirror['Package_type'] == 'deb' ? 'red' : 'blue';

// Description and tags are edited with the repositories fields and events (events/repo/edit.js), the mirror Id is prefixed with 'mirror-'
$editId = 'mirror-' . $mirror['Id'];
$canEditMirror = RepoPermission::allowedAction('edit');
$descriptionClass = $canEditMirror ? 'repo-description-editable' : '';
$tagsClass = $canEditMirror ? 'repo-tags-editable' : '';

if ($canEditMirror && empty($mirror['Description'])) {
    $descriptionClass .= ' repo-description-empty';
}

if ($canEditMirror && empty($mirror['Tags'])) {
    $tagsClass .= ' repo-tags-empty';
}

$mirrorUrl = WWW_MIRRORS_DIR_URL . '/' . htmlspecialchars(Mirror::getDirOf($mirror)); ?>

<!-- repo-id is used as a unique key by the repositories list partial reload, it must not collide with repositories Ids -->
<div class="repo-item-wrapper <?= !empty($expandAlone) ? 'repo-span-full' : '' ?> <?= !empty($userPreferences['repositories']['list']['expand']) ? 'height-100' : '' ?>" repo-id="mirror-<?= $mirror['Id'] ?>" mirror-id="<?= $mirror['Id'] ?>">
    <div class="div-generic-blue margin-0 repo-item accent-<?= $accent ?> overflowx-auto" data-name="<?= htmlspecialchars($mirror['Name']) ?>" data-dist="<?= htmlspecialchars(implode(' ', $mirror['params']['dists'] ?? [])) ?>" data-section="<?= htmlspecialchars(implode(' ', $mirror['params']['components'] ?? [])) ?>" data-releasever="<?= htmlspecialchars($mirror['Releasever']) ?>" data-type="mirror" data-package-type="<?= $mirror['Package_type'] ?>" data-description="<?= htmlspecialchars($mirror['Description'] ?? '') ?>" data-tags="<?= htmlspecialchars($mirror['Tags'] ?? '') ?>">
        <div class="flex row-gap-15 column-gap-20 justify-space-between">
            <div class="flex flex-wrap align-item-center row-gap-15 column-gap-15">
                <p class="font-size-16 mediumopacity-cst flex align-item-center column-gap-5">
                    <span title="Mirror name" class="repo-name"><?= htmlspecialchars($mirror['Name']) ?></span>
                    <?php
                    if (!empty($mirror['params']['dists'])) : ?>
                        <span class="dot">●</span>
                        <span title="Distribution(s)"><?= htmlspecialchars(implode(', ', $mirror['params']['dists'])) ?></span>
                        <span class="dot">●</span>
                        <span title="Components"><?= htmlspecialchars(implode(', ', $mirror['params']['components'] ?? [])) ?></span>
                        <?php
                    endif;

                    if (!empty($mirror['params']['releasever'])) : ?>
                        <span class="dot">●</span>
                        <span title="Release version"><?= htmlspecialchars($mirror['params']['releasever']) ?></span>
                        <?php
                    endif ?>
                </p>

                <?php
                if (RepoPermission::allowedAction('update')) : ?>
                    <div class="repo-rename-btn mirror-sync-btn" mirror-id="<?= $mirror['Id'] ?>" title="Sync this mirror into a new snapshot">
                        <img src="/assets/icons/update.svg" class="icon-np icon-small" />
                        <span>Sync</span>
                    </div>
                    <?php
                endif;

                if (RepoPermission::allowedAction('delete')) : ?>
                    <div class="repo-rename-btn mirror-delete-btn" mirror-id="<?= $mirror['Id'] ?>" mirror-name="<?= htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) ?>" title="Delete this mirror with all its snapshots">
                        <img src="/assets/icons/delete.svg" class="icon-np icon-small" />
                        <span>Delete</span>
                    </div>
                    <?php
                endif;

                if ($canEditMirror) : ?>
                    <div class="repo-add-description-btn" repo-id="<?= $editId ?>" title="Add a description"<?= empty($mirror['Description']) ? '' : ' style="display: none;"' ?>>
                        <img src="/assets/icons/plus.svg" class="icon-np icon-small" />
                        <span>Description</span>
                    </div>

                    <div class="repo-add-tags-btn" repo-id="<?= $editId ?>" title="Add tags"<?= empty($mirror['Tags']) ? '' : ' style="display: none;"' ?>>
                        <img src="/assets/icons/plus.svg" class="icon-np icon-small" />
                        <span>Tags</span>
                    </div>
                    <?php
                endif ?>
            </div>

            <div class="flex align-item-start column-gap-15 mediumopacity-cst">
                <div class="flex align-item-center column-gap-5" title="This repository is a mirror of the <?= htmlspecialchars($mirror['Source']) ?> source repository: upstream metadata and signatures are copied as is, clients use the upstream GPG key.">
                    <img src="/assets/icons/duplicate.svg" class="icon-np icon-medium" />
                    <span class="font-size-13">mirror</span>
                </div>

                <div class="flex align-item-center column-gap-5" title="This is a <?= $mirror['Package_type'] ?> mirror. Architectures: <?= htmlspecialchars(implode(', ', $mirror['params']['arch'] ?? [])) ?>">
                    <img src="/assets/icons/package.svg" class="icon-np icon-medium" />
                    <span class="font-size-13"><?= $mirror['Package_type'] ?></span>
                </div>
            </div>
        </div>

        <div class="repo-description-container"<?= empty($mirror['Description']) ? ' style="display: none;"' : '' ?>>
            <p class="note repo-description-input width-100 <?= $descriptionClass ?>" repo-id="<?= $editId ?>" env-id="" title="Double-click to edit the description"><?= htmlspecialchars($mirror['Description'] ?? '') ?></p>
        </div>

        <div class="repo-snapshots flex flex-1 flex-direction-column row-gap-10 margin-top-20 margin-bottom-5">
            <?php
            if (empty($mirror['snapshots'])) : ?>
                <p class="note">No snapshot</p>
                <?php
            endif;

            foreach ($mirror['snapshots'] as $snapshot) :
                $date = DateTime::createFromFormat('Y-m-d', $snapshot['Date'])->format('d-m-Y');
                $time = substr($snapshot['Time'], 0, 5);
                $snapshotUrl = $mirrorUrl . '/' . $snapshot['dirName'] . '/'; ?>

                <div class="snap-container mirror-snap-container grid-rfr-1-2 pointer" mirror-id="<?= $mirror['Id'] ?>" mirror-snap-id="<?= $snapshot['Id'] ?>" snap-label="<?= $date . ' ' . $snapshot['Time'] ?>" title="Click to select">
                    <input type="checkbox" class="snap-checkbox-input mirror-snap-checkbox" />
                    <div class="flex align-item-center column-gap-20">
                        <div class="flex align-item-center column-gap-10" title="Snapshot created at <?= $date . ' ' . $snapshot['Time'] ?>. Click to browse content">
                            <img src="/assets/icons/calendar.svg" class="snap-icon icon-np lowopacity-cst" />
                            <a href="<?= $snapshotUrl ?>" target="_blank" rel="noopener noreferrer"><span class="snap-date lowopacity"><?= $date ?></span></a>
                            <span class="snap-size lowopacity-cst"><?= $time ?></span>
                        </div>

                        <div class="snap-separator"></div>

                        <div class="flex flex-wrap column-gap-20 row-gap-10">
                            <div class="flex align-item-center column-gap-6" title="Mirror snapshot size">
                                <img src="/assets/icons/package.svg" class="icon-medium icon-np mediumopacity-cst" />
                                <span class="snap-size mediumopacity-cst"><?= !empty($snapshot['Size']) ? Convert::sizeToHuman($snapshot['Size']) : '?' ?></span>
                            </div>

                            <div class="flex align-item-center column-gap-6">
                                <?php
                                if (($mirror['params']['gpg-check'] ?? 'true') == 'true') : ?>
                                    <img src="/assets/icons/check.svg" class="icon-medium icon-np" />
                                    <span class="snap-signed mediumopacity-cst" title="Upstream GPG signature was verified during sync">Verified</span>
                                    <?php
                                else : ?>
                                    <img src="/assets/icons/error.svg" class="icon-medium icon-np" />
                                    <span class="snap-signed mediumopacity-cst" title="Upstream GPG signature was not verified during sync">Unverified</span>
                                    <?php
                                endif ?>
                            </div>
                        </div>
                    </div>

                    <div class="snap-envs">
                        <?php
                        foreach ($snapshot['envs'] as $env) :
                            $protectedEnv = in_array($env, $protectedEnvs, true); ?>

                            <div class="snap-env-container mirror-env-container<?= $protectedEnv ? ' nopointer' : '' ?>" mirror-id="<?= $mirror['Id'] ?>" env="<?= htmlspecialchars($env) ?>" title="<?= $protectedEnv ? 'Protected environment' : 'Click to select' ?>"<?= $protectedEnv ? ' data-protected="true"' : '' ?>>
                                <?= Label::envtag($env, 'snap-env') ?>
                            </div>
                            <?php
                        endforeach ?>
                    </div>
                </div>
                <?php
            endforeach ?>
        </div>

        <?php
        // Client URL for each environment
        if (!empty($mirror['envs'])) : ?>
            <div class="flex flex-direction-column row-gap-5 margin-top-10">
                <?php
                foreach (array_unique($mirror['envs']) as $env) : ?>
                    <div class="flex align-item-center column-gap-5 mediumopacity" title="URL to use on clients for the <?= htmlspecialchars($env) ?> environment">
                        <img src="/assets/icons/link.svg" class="icon-np icon-small" />
                        <p class="font-size-13 copy wordbreakall"><?= $mirrorUrl . '/' . htmlspecialchars($env) . '/' ?></p>
                    </div>
                    <?php
                endforeach ?>
            </div>
            <?php
        endif ?>

        <div class="repo-tags-container"<?= empty($mirror['Tags']) ? ' style="display: none;"' : '' ?>>
            <div class="repo-tags-display flex align-item-center flex-wrap column-gap-10 <?= $tagsClass ?>" repo-id="<?= $editId ?>" data-tags="<?= htmlspecialchars($mirror['Tags'] ?? '') ?>">
                <?php
                if (!empty($mirror['Tags'])) :
                    foreach (explode(',', $mirror['Tags']) as $tag) : ?>
                        <div class="flex align-item-center column-gap-5 mediumopacity repo-tag-item" title="Click to filter by <?= htmlspecialchars($tag) ?> tag. Double-click to edit tags." repo-id="<?= $editId ?>">
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
<?php
unset($editId, $canEditMirror, $descriptionClass, $tagsClass);
