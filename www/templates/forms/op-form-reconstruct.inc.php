<tr>
    <td colspan="100%">
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo 'L\'opération va reconstruire les metadonnées de : <br><br><span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo 'L\'opération va reconstruire les metadonnées de : <br><br><span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        } ?>
    </td>
</tr>

<tr>
    <td class="td-30">Signer avec GPG</td>
    <td>
        <label class="onoff-switch-label">
            <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param" value="yes" <?php echo (GPG_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?>>
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>