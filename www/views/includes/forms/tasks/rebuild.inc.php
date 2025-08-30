<h6>REBUILD METADATA</h6>
<p class="note">The repository snapshot to rebuild metadata for.</p>

<div class="flex align-item-center">
    <p class="label-white">
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo $myrepo->getName() . ' ❯ ' . $myrepo->getReleasever();
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection();
        } ?>
    </p>

    <p>⸺<span class="label-black"><?= $myrepo->getDateFormatted() ?></span></p>
</div>

<h6>SIGN WITH GPG</h6>
<p class="note">Sign repository / packages with GPG.</p>
<label class="onoff-switch-label">
    <?php
    if ($myrepo->getPackageType() == 'rpm') : ?>
        <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param" value="true" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?>>
        <?php
    endif;

    if ($myrepo->getPackageType() == 'deb') : ?>
        <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param" value="true" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?>>
        <?php
    endif ?>
    <span class="onoff-switch-slider"></span>
</label>

<?php
/**
 *  Define schedule form action and allowed type(s)
 */
$scheduleForm['action'] = 'rebuild';
$scheduleForm['type'] = array('unique', 'recurring'); ?>