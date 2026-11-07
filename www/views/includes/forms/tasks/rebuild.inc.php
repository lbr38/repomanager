<div class="flex align-item-center">
    <p class="label-white">
        <?php
        if ($repoController->getPackageType() == 'rpm') {
            echo $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
        }
        if ($repoController->getPackageType() == 'deb') {
            echo $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
        } ?>
    </p>

    <p>⸺<span class="label-black"><?= $repoController->getDateFormatted() ?></span></p>
</div>

<?php
// A snapshot used by a protected environment cannot be rebuilt, the rebuild would be refused
$protectedEnvs = (new \Controllers\Repo\Snapshot\Snapshot())->getProtectedEnvs((int) $snapId);

if (!empty($protectedEnvs)) : ?>
    <div class="note-warning">
        <img src="/assets/icons/warning.svg" />
        <p>This snapshot is used by <?= \Controllers\Environment::nameList($protectedEnvs, 'protected') ?> and cannot be rebuilt.</p>
    </div>
    <?php
endif ?>

<h6>SIGN WITH GPG</h6>
<p class="note">Sign repository / packages with GPG. Pre-selected according to the current snapshot signature.</p>
<label class="onoff-switch-label">
    <?php
    if ($repoController->getPackageType() == 'rpm') : ?>
        <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param" value="true" <?php echo ($repoController->getSigned() == 'true') ? 'checked' : ''; ?>>
        <?php
    endif;

    if ($repoController->getPackageType() == 'deb') : ?>
        <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param" value="true" <?php echo ($repoController->getSigned() == 'true') ? 'checked' : ''; ?>>
        <?php
    endif ?>
    <span class="onoff-switch-slider"></span>
</label>

<?php
// Define schedule form action (useful for the schedule form)
$scheduleForm['action'] = 'rebuild'; ?>