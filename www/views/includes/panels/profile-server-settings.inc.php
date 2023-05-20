<?php ob_start(); ?>

<form id="applyServerConfigurationForm" autocomplete="off">
    <div class="grid grid-2 align-item-center row-gap-4">
        <span>
            <img src="assets/icons/info.svg" class="icon-verylowopacity" title="Handled package types, defined from enabled repo types on this server." />Handled packages
        </span>
        <span>
            <?= $serverPackageType ?>
        </span>
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