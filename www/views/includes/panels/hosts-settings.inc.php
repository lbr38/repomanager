<?php ob_start(); ?>

<form id="hostsSettingsForm" action="/hosts" method="post" autocomplete="off">
    <table>
        <tr>
            <td>Display a yellow label when total available update is greater than or equal to:</td>
            <td><input type="number" class="input-small" name="settings-pkgs-considered-outdated" value="<?= $pkgs_count_considered_outdated ?>" /></td>
        </tr>
        <tr>
            <td>Display a red label when total available update is greater than or equal to:</td>
            <td><input type="number" class="input-small" name="settings-pkgs-considered-critical" value="<?= $pkgs_count_considered_critical ?>" /></td>
        </tr>
    </table>
    <br>
    <button class="btn-large-green">Save</button>
</form>

<?php
$content = ob_get_clean();
$slidePanelName = 'hosts-settings';
$slidePanelTitle = 'HOSTS DISPLAY SETTINGS';

include(ROOT . '/views/includes/slide-panel.inc.php');