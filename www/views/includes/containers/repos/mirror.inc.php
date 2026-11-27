<?php
use \Controllers\Mirror\Mirror;
use \Controllers\Utils\Convert;
use \Controllers\Utils\Generate\Html\Label;
use \Controllers\User\Permission\Mirror as MirrorPermission;

$accent = Mirror::getAccent($mirror['Package_type']);

// Description and tags are edited with the repositories fields and events (events/repo/edit.js), the mirror Id is prefixed with 'mirror-'
$editId = 'mirror-' . $mirror['Id'];
$canEditMirror = MirrorPermission::allowedAction('edit');
$descriptionClass = $canEditMirror ? 'repo-description-editable' : '';
$tagsClass = $canEditMirror ? 'repo-tags-editable' : '';

if ($canEditMirror && empty($mirror['Description'])) {
    $descriptionClass .= ' repo-description-empty';
}

if ($canEditMirror && empty($mirror['Tags'])) {
    $tagsClass .= ' repo-tags-empty';
}

// Comma separated list of values, a line can only break after a comma, not inside a value (e.g. 'non-free')
$nowrapList = function (array $values): string {
    return implode(', ', array_map(function ($value) {
        return '<span class="nowrap">' . htmlspecialchars($value) . '</span>';
    }, $values));
}; ?>

<!-- repo-id is used as a unique key by the repositories list partial reload, it must not collide with repositories Ids -->
<div class="repo-item-wrapper <?= !empty($expandAlone) ? 'repo-span-full' : '' ?> <?= !empty($userPreferences['repositories']['list']['expand']) ? 'height-100' : '' ?>" repo-id="mirror-<?= $mirror['Id'] ?>" mirror-id="<?= $mirror['Id'] ?>">
    <div class="div-generic-blue margin-0 repo-item accent-<?= $accent ?> overflowx-auto" data-name="<?= htmlspecialchars($mirror['Name']) ?>" data-dist="<?= htmlspecialchars(implode(' ', array_merge($mirror['params']['dists'] ?? [], $mirror['params']['repos'] ?? [], $mirror['params']['versions'] ?? []))) ?>" data-section="<?= htmlspecialchars(implode(' ', $mirror['params']['components'] ?? [])) ?>" data-releasever="<?= htmlspecialchars($mirror['Releasever']) ?>" data-type="mirror" data-package-type="<?= $mirror['Package_type'] ?>" data-description="<?= htmlspecialchars($mirror['Description'] ?? '') ?>" data-tags="<?= htmlspecialchars($mirror['Tags'] ?? '') ?>">
        <div class="repo-item-header flex flex-wrap row-gap-10 column-gap-20 justify-space-between">
            <div class="flex flex-1 flex-wrap align-item-center row-gap-15 column-gap-15">
                <p class="font-size-16 mediumopacity-cst">
                    <!-- flex-wrap moves whole items to the next line instead of breaking their text, each dot stays with the item it precedes -->
                    <a href="/repository/mirror/<?= $mirror['Id'] ?>" class="flex flex-wrap align-item-center row-gap-5 column-gap-5">
                        <span title="Mirror name" class="repo-name nowrap"><?= htmlspecialchars($mirror['Name']) ?></span>
                        <?php
                        if (!empty($mirror['params']['dists'])) : ?>
                            <span class="flex align-item-center column-gap-5"><span class="dot">●</span><span title="Distribution(s)"><?= $nowrapList($mirror['params']['dists']) ?></span></span>
                            <span class="flex align-item-center column-gap-5"><span class="dot">●</span><span title="Components"><?= $nowrapList($mirror['params']['components'] ?? []) ?></span></span>
                            <?php
                        endif;

                        if (!empty($mirror['params']['releasever'])) : ?>
                            <span class="flex align-item-center column-gap-5"><span class="dot">●</span><span title="Release version"><?= htmlspecialchars($mirror['params']['releasever']) ?></span></span>
                            <?php
                        endif;

                        if (!empty($mirror['params']['branch'])) : ?>
                            <span class="flex align-item-center column-gap-5"><span class="dot">●</span><span title="Branch"><?= htmlspecialchars($mirror['params']['branch']) ?></span></span>
                            <?php
                        endif;

                        if (!empty($mirror['params']['repos'])) : ?>
                            <span class="flex align-item-center column-gap-5"><span class="dot">●</span><span title="Repositories"><?= $nowrapList($mirror['params']['repos']) ?></span></span>
                            <?php
                        endif;

                        if (!empty($mirror['params']['versions'])) : ?>
                            <span class="flex align-item-center column-gap-5"><span class="dot">●</span><span title="Versions"><?= $nowrapList($mirror['params']['versions']) ?></span></span>
                            <span class="flex align-item-center column-gap-5"><span class="dot">●</span><span title="Content"><?= $nowrapList($mirror['params']['content'] ?? []) ?></span></span>
                            <?php
                        endif ?>
                    </a>
                </p>

                <?php
                if ($canEditMirror) : ?>
                    <div class="repo-header-btn mirror-edit-btn" mirror-id="<?= $mirror['Id'] ?>" title="Edit mirror properties">
                        <img src="/assets/icons/cog.svg" class="icon-np icon-small" />
                        <span>Edit</span>
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

            <div class="flex flex-wrap justify-end align-item-start row-gap-5 column-gap-15 margin-left-auto mediumopacity-cst nowrap">
                <!-- Source repository, followed by the repository type in a discreet way -->
                <div class="flex flex-wrap justify-end align-item-center column-gap-5" title="Mirror of the <?= htmlspecialchars($mirror['Source']) ?> source repository: upstream metadata and signatures are copied as is, clients use the upstream <?= Mirror::getSignatureType($mirror['Package_type']) ?> key.">
                    <img src="/assets/icons/internet.svg" class="icon-np icon-medium" />
                    <span class="font-size-13"><?= htmlspecialchars($mirror['Source']) ?></span>
                    <span class="font-size-13 lowopacity-cst">● mirror</span>
                </div>

                <div class="flex align-item-center column-gap-5" title="This is a <?= $mirror['Package_type'] ?> mirror.">
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
                $time = substr($snapshot['Time'], 0, 5); ?>

                <div class="snap-container mirror-snap-container grid-rfr-1-2 pointer" mirror-id="<?= $mirror['Id'] ?>" mirror-snap-id="<?= $snapshot['Id'] ?>" snap-label="<?= $date . ' ' . $snapshot['Time'] ?>" title="Click to select">
                    <input type="checkbox" class="snap-checkbox-input mirror-snap-checkbox" />
                    <div class="flex align-item-center column-gap-20">
                        <!-- empty title prevents the parent "Click to select" native tooltip from showing over the snapshot tooltip -->
                        <div class="flex align-item-center column-gap-10" title="" data-tooltip-template>
                            <img src="/assets/icons/calendar.svg" class="snap-icon icon-np lowopacity-cst" />
                            <a href="/snapshot/mirror/<?= $snapshot['Id'] ?>"><span class="snap-date lowopacity"><?= $date ?></span></a>
                            <span class="snap-size lowopacity-cst"><?= $time ?></span>
                            <template>
                                <p class="tooltip-title">Snapshot</p>
                                <div class="tooltip-properties">
                                    <span class="tooltip-property-label">Created</span>
                                    <span class="tooltip-property-value"><?= $date . ' ' . $time ?></span>
                                    <span class="tooltip-property-label">Architecture</span>
                                    <span class="tooltip-property-value"><?= !empty($mirror['params']['arch']) ? htmlspecialchars(implode(', ', $mirror['params']['arch'])) : '?' ?></span>
                                </div>
                                <p class="note margin-top-5">Click to browse content</p>
                            </template>
                        </div>

                        <div class="snap-separator"></div>

                        <div class="flex flex-wrap column-gap-20 row-gap-10">
                            <div class="flex align-item-center column-gap-6" title="Mirror snapshot size">
                                <img src="/assets/icons/package.svg" class="icon-medium icon-np mediumopacity-cst" />
                                <span class="snap-size mediumopacity-cst"><?= !empty($snapshot['Size']) ? Convert::sizeToHuman($snapshot['Size']) : '?' ?></span>
                            </div>

                            <div class="flex align-item-center column-gap-6">
                                <?php
                                // Most Arch Linux repositories do not sign their database, packages signatures are checked by pacman on clients
                                $archRepoDir = $mirror['Package_type'] == 'arch' ? ($mirror['params']['repos'][0] ?? '') . '/os/' . ($mirror['params']['arch'][0] ?? '') . '/' . ($mirror['params']['repos'][0] ?? '') : '';
                                $unsignedArchDb = $mirror['Package_type'] == 'arch' && !is_file(Mirror::getPath(Mirror::getDirOf($mirror)) . '/' . $snapshot['dirName'] . '/' . $archRepoDir . '.db.sig');

                                $signatureType = Mirror::getSignatureType($mirror['Package_type']);

                                if (($mirror['params']['gpg-check'] ?? 'true') == 'true' && $unsignedArchDb) : ?>
                                    <img src="/assets/icons/key.svg" class="icon-medium icon-np mediumopacity-cst" />
                                    <span class="snap-signed mediumopacity-cst" title="The database is not signed by the source repository (as for Arch Linux official repositories): packages signatures are mirrored and checked by pacman on clients">Unsigned database</span>
                                    <?php
                                elseif (($mirror['params']['gpg-check'] ?? 'true') == 'true') : ?>
                                    <img src="/assets/icons/check.svg" class="icon-medium icon-np" />
                                    <span class="snap-signed mediumopacity-cst" title="Upstream <?= $signatureType ?> signature was verified during sync">Verified</span>
                                    <?php
                                else : ?>
                                    <img src="/assets/icons/error.svg" class="icon-medium icon-np" />
                                    <span class="snap-signed mediumopacity-cst" title="Upstream <?= $signatureType ?> signature was not verified during sync">Unverified</span>
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
