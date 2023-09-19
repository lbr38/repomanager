<?php
if ($myrepo->getPackageType() == 'rpm') {
    $mirror = $myrepo->getName();
}
if ($myrepo->getPackageType() == 'deb') {
    $mirror = $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection();
} ?>

<tr>
    <td colspan="100%">Operation will create a new mirror snapshot:
    <br><br><span class="label-white"><?= $mirror ?></span>⟶<span class="label-green"><?= DATE_DMY ?></span><span id="update-repo-show-target-env-<?= $myrepo->getSnapId() ?>"></span></td>
</tr>

<tr>
    <td colspan="100%">Update params:</td>
</tr>

<tr>
    <td class="td-30">Check GPG signatures</td>
    <td>
        <label class="onoff-switch-label">
            <input name="repoGpgCheck" param-name="targetGpgCheck" type="checkbox" class="onoff-switch-input operation_param" value="yes" checked />
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>

<tr>
    <td class="td-30">Sign with GPG</td>
    <td>
        <label class="onoff-switch-label">
            <?php
            if ($myrepo->getPackageType() == 'rpm') : ?>
                <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param type_rpm" value="yes" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?>>
                <?php
            endif;
            if ($myrepo->getPackageType() == 'deb') : ?>
                <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param type_deb" value="yes" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?>>
                <?php
            endif ?>
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>

<tr>
    <td class="td-30">Point an environment</td>
    <td>
        <select id="update-repo-target-env-select-<?= $myrepo->getSnapId() ?>" class="operation_param" param-name="targetEnv">
            <option value=""></option>
            <?php
            foreach (ENVS as $env) {
                if ($env == DEFAULT_ENV) {
                    echo '<option value="' . $env . '" selected>' . $env . '</option>';
                } else {
                    echo '<option value="' . $env . '">' . $env . '</option>';
                }
            } ?>
        </select>
    </td>
</tr>

<tr>
    <td class="td-30">Architecture</td>
    <td>
        <select class="targetArchSelect operation_param" param-name="targetArch" multiple>
            <option value="">Select architecture...</option>
            <?php
            if ($myrepo->getPackageType() == 'rpm') :
                foreach (RPM_ARCHS as $arch) {
                    if (in_array($arch, RPM_DEFAULT_ARCH)) {
                        echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                    } else {
                        echo '<option value="' . $arch . '">' . $arch . '</option>';
                    }
                }
            endif;
            if ($myrepo->getPackageType() == 'deb') :
                foreach (DEB_ARCHS as $arch) {
                    if (in_array($arch, DEB_DEFAULT_ARCH)) {
                        echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                    } else {
                        echo '<option value="' . $arch . '">' . $arch . '</option>';
                    }
                }
            endif; ?>
        </select>
    </td>
</tr>

<tr>
    <td class="td-30">Include sources packages</td>
    <td>
        <?php
        if ($myrepo->getPackageType() == 'rpm') : ?>
            <label class="onoff-switch-label">
                <input name="repoIncludeSource" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetSourcePackage" <?php echo (RPM_INCLUDE_SOURCE == 'true') ? 'checked' : ''; ?> />
                <span class="onoff-switch-slider"></span>
            </label>
            <?php
        endif;
        if ($myrepo->getPackageType() == 'deb') : ?>
            <label class="onoff-switch-label">
                <input name="repoIncludeSource" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetSourcePackage" <?php echo (DEB_INCLUDE_SOURCE == 'true') ? 'checked' : ''; ?> />
                <span class="onoff-switch-slider"></span>
            </label>
            <?php
        endif; ?>
    </td>
</tr>

<?php
if ($myrepo->getPackageType() == 'deb') : ?>
<!-- <tr>
    <td class="td-30">Include translations</td>
    <td>
        <select class="targetPackageTranslationSelect operation_param" param-name="targetPackageTranslation" multiple>
            <option value="en" <?php //echo (in_array('en', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>en (english)</option>
            <option value="fr" <?php //echo (in_array('fr', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>fr (french)</option>
            <option value="de" <?php //echo (in_array('de', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>de (deutsch)</option>
            <option value="it" <?php //echo (in_array('it', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>it (italian)</option>
        </select>
    </td>
</tr> -->
    <?php
endif; ?>

<tr>
    <td class="td-30" title="Selected snapshot content will be copied to the new snapshot before syncing. Then only the new changed packages will be synced from source repository. Can significantly reduce syncing duration on large repos.">Only sync the difference</td>
    <td>
        <label class="onoff-switch-label">
            <input type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="onlySyncDifference" checked />
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>

<script>
$(document).ready(function(){
    /**
     *  Convert select to select2
     */
    classToSelect2('.targetArchSelect');
    classToSelect2('.targetPackageTranslationSelect');

    /**
     *  Update repo->date<-env schema if an env is selected
     */
    var selectName = '#update-repo-target-env-select-<?= $myrepo->getSnapId() ?>';
    var envSpan = '#update-repo-show-target-env-<?= $myrepo->getSnapId() ?>';

    function printEnv() {
        /**
         *  Nom du dernier environnement de la chaine
         */
        var lastEnv = '<?= LAST_ENV ?>';

        /**
         *  Récupération de l'environnement sélectionné dans la liste
         */
        var selectValue = $(selectName).val();
        
        /**
         *  Si l'environnement correspond au dernier environnement de la chaine alors il sera affiché en rouge
         */
        if (selectValue == lastEnv) {
            var envSpanClass = 'last-env';
        } else {            
            var envSpanClass = 'env';
        }

        /**
         *  Si aucun environnement n'a été selectionné par l'utilisateur alors on n'affiche rien 
         */
        if (selectValue == "") {
            $(envSpan).html('');
        
        /**
         *  Sinon on affiche l'environnement qui pointe vers le nouveau snapshot qui sera créé
         */
        } else {
            $(envSpan).html('⟵<span class="'+envSpanClass+'">'+selectValue+'</span>');
        }
    }

    printEnv();

    $(document).on('change',selectName,function(){
        printEnv();
    }).trigger('change');
});
</script>