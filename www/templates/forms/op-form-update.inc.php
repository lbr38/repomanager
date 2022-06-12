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
            <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param type_rpm" value="yes" <?php echo (RPM_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?>>
            <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param type_deb" value="yes" <?php echo (DEB_SIGN_REPO == "yes") ? 'checked' : ''; ?>>
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

<script>
$(document).ready(function(){

    var selectName = '#update-repo-target-env-select-<?=$myrepo->getSnapId()?>';
    var envSpan = '#update-repo-show-target-env-<?=$myrepo->getSnapId()?>';

    function printEnv() {
        /**
         *  Nom du dernier environnement de la chaine
         */
        var lastEnv = '<?=LAST_ENV?>';

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