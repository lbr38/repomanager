<?php
if ($myrepo->getPackageType() == 'rpm') {
    $mirror = $myrepo->getName();
}
if ($myrepo->getPackageType() == 'deb') {
    $mirror = $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection();
}
?>

<tr>
    <td colspan="100%">L'opération va créer un nouveau miroir :<br><br><span class="label-white"><?=$mirror?></span>⟶<span class="label-green"><?=DATE_DMY?></span><span id="update-repo-show-target-env-<?=$myrepo->getSnapId()?>"></span></td>
</tr>

<tr>
    <td colspan="100%">Paramétrage de la mise à jour :</td>
</tr>

<tr>
    <td class="td-30">Vérification des signatures GPG</td>
    <td>
        <label class="onoff-switch-label">
            <input name="repoGpgCheck" param-name="targetGpgCheck" type="checkbox" class="onoff-switch-input operation_param" value="yes" checked />
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>

<tr>
    <td class="td-30">Signer avec GPG</td>
    <td>
        <label class="onoff-switch-label">
            <?php
            if ($myrepo->getPackageType() == 'rpm') : ?>
                <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param type_rpm" value="yes" <?php echo (RPM_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?>>
                <?php
            endif;
            if ($myrepo->getPackageType() == 'deb') : ?>
                <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param type_deb" value="yes" <?php echo (DEB_SIGN_REPO == "yes") ? 'checked' : ''; ?>>
            <?php endif ?>
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>

<tr>
    <td class="td-30">Faire pointer un environnement</td>
    <td>
        <select id="update-repo-target-env-select-<?=$myrepo->getSnapId()?>" class="operation_param" param-name="targetEnv">
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
        <select class="targetIncludeArchSelect operation_param" param-name="targetIncludeArch" multiple>
            <option value="">Sélectionner l'architecture...</option>
            <?php
            if ($myrepo->getPackageType() == 'rpm') : ?>
                <option value="x86_64" <?php echo (in_array('x86_64', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>x86_64</option>
                <option value="noarch" <?php echo (in_array('noarch', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>noarch</option>
                <?php
            endif;
            if ($myrepo->getPackageType() == 'deb') : ?>
                <option value="i386" <?php echo (in_array('i386', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>i386</option>
                <option value="amd64" <?php echo (in_array('amd64', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>amd64</option>
                <option value="armhf" <?php echo (in_array('armhf', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>armhf</option>
                <?php
            endif; ?>
        </select>
    </td>
</tr>

<tr>
    <td class="td-30">Inclure les sources</td>
    <td>
        <?php
        if ($myrepo->getPackageType() == 'rpm') : ?>
            <label class="onoff-switch-label">
                <input name="repoIncludeSource" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetIncludeSource" <?php echo (RPM_INCLUDE_SOURCE == 'yes') ? 'checked' : ''; ?> />
                <span class="onoff-switch-slider"></span>
            </label>
            <?php
        endif;
        if ($myrepo->getPackageType() == 'deb') : ?>
            <label class="onoff-switch-label">
                <input name="repoIncludeSource" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetIncludeSource" <?php echo (DEB_INCLUDE_SOURCE == 'yes') ? 'checked' : ''; ?> />
                <span class="onoff-switch-slider"></span>
            </label>
            <?php
        endif; ?>
    </td>
</tr>

<?php
if ($myrepo->getPackageType() == 'deb') : ?>
<tr>
    <td class="td-30">Inclure les traductions de paquets</td>
    <td>
        <select class="targetIncludeTranslationSelect operation_param" param-name="targetIncludeTranslation" multiple>
            <option value="">Sélectionner des traductions...</option>
            <option value="en" <?php echo (in_array('en', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>en (english)</option>
            <option value="fr" <?php echo (in_array('fr', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>fr (french)</option>
        </select>
    </td>
</tr>
<?php
endif; ?>

<script>
$(document).ready(function(){
    /**
     *  Convert select to select2
     */
    classToSelect2('.targetIncludeArchSelect');
    classToSelect2('.targetIncludeTranslationSelect');


    /**
     *  Update repo->date<-env schema if an env is selected
     */
    var selectName = '#update-repo-target-env-select-<?=$myrepo->getSnapId()?>';
    var envSpan = '#update-repo-show-target-env-<?=$myrepo->getSnapId()?>';

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