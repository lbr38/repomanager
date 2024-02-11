<tr>
    <td colspan="100%">
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo 'Operation will rebuild metadata of: <br><br><span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo 'Operation will rebuild metadata of: <br><br><span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        } ?>
    </td>
</tr>

<tr>
    <td class="td-30">Sign with GPG</td>
    <td>
        <label class="onoff-switch-label">
            <?php if ($myrepo->getPackageType() == 'rpm') : ?>
                <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param" value="yes" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?>>
            <?php endif ?>
            <?php if ($myrepo->getPackageType() == 'deb') : ?>
                <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param" value="yes" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?>>
            <?php endif ?>
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>