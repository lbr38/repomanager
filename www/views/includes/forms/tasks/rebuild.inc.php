<h6>REBUILD METADATA OF</h6>

<?php
if ($myrepo->getPackageType() == 'rpm') {
    echo '<span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
}
if ($myrepo->getPackageType() == 'deb') {
    echo '<span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
} ?>

<h6>SIGN WITH GPG</h6>
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