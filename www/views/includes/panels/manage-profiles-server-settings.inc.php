<?php ob_start(); ?>

<form id="applyServerConfigurationForm" autocomplete="off">
    <?php
    /**
     *  Si une des valeurs était vide alors on indique à l'utilisateur qu'il faut valider le formulaire au moins une fois pour valider et appliquer la configuration.
     */
    if ($serverConfApplyNeeded > 0) {
        echo '<p><img src="assets/icons/warning.png" class="icon" />Some parameters were empty and have been generated automatically. You must validate this form to apply configuration.<br><br></p>';
    } ?>
    <div class="operation-form">
        <input type="hidden" id="serverPackageTypeInput" class="td-medium" value="<?=$serverPackageType?>" />
        <span>
            <img src="assets/icons/info.svg" class="icon-verylowopacity" title="If enabled, this server will be able to specify repos files for each profile." />Manage profiles repos configuration
        </span>
        <label class="onoff-switch-label">
            <input id="serverManageClientRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo ($serverManageClientRepos == "yes") ? 'checked' : ''; ?>>
            <span class="onoff-switch-slider"></span>
        </label>
        <span>
            <img src="assets/icons/info.svg" class="icon-verylowopacity" title="If enabled, this server will be able to specify which package(s) to exclude for each profile." />Manage profiles packages configuration
        </span>
        <label class="onoff-switch-label">
            <input id="serverManageClientConf" type="checkbox" class="onoff-switch-input" value="yes" <?php echo ($serverManageClientConf == "yes") ? 'checked' : ''; ?>>
            <span class="onoff-switch-slider"></span>
        </label>
    </div>
    <br>
    <button type="submit" class="btn-large-green">Save</button>
</form>

<?php
$content = ob_get_clean();
$slidePanelName = 'manage-profiles-server-settings';
$slidePanelTitle = 'SERVER SETTINGS';

include(ROOT . '/views/includes/slide-panel.inc.php');