<?php
use \Controllers\Utils\Generate\Html\Label;

// Mirror info is in the raw params (create) or in the database (update)
$mirrorInfo = [
    'Name'         => $rawParams['name'] ?? '',
    'Releasever'   => ($rawParams['package-type'] ?? '') == 'rpm' ? ($rawParams['releasever'] ?? '') : '',
    'Package_type' => $rawParams['package-type'] ?? '',
    'Source'       => $rawParams['source'] ?? ''
];
$mirrorParams = $rawParams;

if (!empty($rawParams['mirror-id']) and is_numeric($rawParams['mirror-id'])) {
    $mirrorController = new \Controllers\Mirror\Mirror();
    $mirrorInfo = $mirrorController->getById((int) $rawParams['mirror-id']);

    if (!empty($mirrorInfo)) {
        $mirrorParams = $mirrorController->getParams($mirrorInfo);
    }
} ?>

<div id="task-details" class="div-generic-blue margin-bottom-15">
    <div class="grid grid-2 row-gap-10 column-gap-20">
        <div>
            <h6 class="margin-top-0">MIRROR</h6>
            <?= Label::white(htmlspecialchars(isset($mirrorInfo['Name']) ? \Controllers\Mirror\Mirror::getLabel($mirrorInfo['Name'], $mirrorInfo['Releasever'] ?? '') : 'Unknown (deleted)')) ?>
        </div>

        <div>
            <h6 class="margin-top-0">SOURCE REPOSITORY</h6>
            <p class="copy"><?= Label::white(htmlspecialchars($mirrorInfo['Source'] ?? '')) ?></p>
        </div>
    </div>

    <div class="grid grid-2 row-gap-10 column-gap-20">
        <?php
        // Distributions are stored as 'dists' in mirror params, they are sent as 'dist' by the creation form
        $mirrorDists = array_filter((array) ($mirrorParams['dists'] ?? $mirrorParams['dist'] ?? []));

        if (!empty($mirrorDists)) : ?>
            <div>
                <h6>DISTRIBUTION(S)</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($mirrorDists as $dist) {
                        echo Label::white(htmlspecialchars($dist));
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($mirrorParams['components'])) : ?>
            <div>
                <h6>COMPONENTS</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($mirrorParams['components'] as $component) {
                        echo Label::white(htmlspecialchars($component));
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($mirrorParams['releasever'])) : ?>
            <div>
                <h6>RELEASE VERSION</h6>
                <?= Label::white(htmlspecialchars($mirrorParams['releasever'])) ?>
            </div>
            <?php
        endif;

        if (!empty($mirrorParams['arch'])) : ?>
            <div>
                <h6>ARCHITECTURE</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ($mirrorParams['arch'] as $arch) {
                        echo Label::white(htmlspecialchars($arch));
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($rawParams['mirror-snap-id']) and is_numeric($rawParams['mirror-snap-id'])) :
            $mirrorSnapshot = (new \Controllers\Mirror\Snapshot())->getById((int) $rawParams['mirror-snap-id']);

            if (!empty($mirrorSnapshot)) : ?>
                <div>
                    <h6>SNAPSHOT</h6>
                    <?= Label::white(\Controllers\Mirror\Mirror::getSnapshotLabel(\Controllers\Mirror\Mirror::getSnapshotDirName($mirrorSnapshot['Date'], $mirrorSnapshot['Time']))) ?>
                </div>
                <?php
            endif;
        endif;

        if (!empty($rawParams['env'])) : ?>
            <div>
                <h6>ENVIRONMENT</h6>
                <div class="flex column-gap-5 row-gap-5 flex-wrap">
                    <?php
                    foreach ((array) $rawParams['env'] as $env) {
                        echo Label::envtag($env);
                    } ?>
                </div>
            </div>
            <?php
        endif;

        if (!empty($mirrorParams['gpg-check'])) : ?>
            <div>
                <h6>CHECK GPG SIGNATURES</h6>
                <div class="flex column-gap-5">
                    <img src="/assets/icons/<?= $mirrorParams['gpg-check'] == 'true' ? 'check' : 'error' ?>.svg" class="icon" />
                    <span><?= $mirrorParams['gpg-check'] == 'true' ? 'Enabled' : 'Disabled' ?></span>
                </div>
            </div>
            <?php
        endif ?>
    </div>
</div>
<?php
unset($mirrorInfo, $mirrorParams, $mirrorController, $mirrorSnapshot, $mirrorDists);
