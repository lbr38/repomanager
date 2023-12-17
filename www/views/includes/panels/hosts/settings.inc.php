<?php ob_start(); ?>

<form id="hostsSettingsForm" action="/hosts" method="post" autocomplete="off">
    <h5>AVAILABLE PACKAGE UPDATES</h5>

    <p class="lowopacity-cst">Display color labels depending on the number of available updates to help you quickly identify hosts that need to be updated.</p>

    <br>

    <p>Display a <span class="label-yellow">yellow</span> label when total available update is greater than or equal to:</p>
    <input type="number" name="settings-pkgs-considered-outdated" value="<?= $pkgs_count_considered_outdated ?>" />
        
    <br><br>

    <p>Display a <span class="label-red">red</span> label when total available update is greater than or equal to:</p>
    <input type="number" name="settings-pkgs-considered-critical" value="<?= $pkgs_count_considered_critical ?>" />

    <br><br>

    <button class="btn-large-green">Save</button>
</form>

<?php
$content = ob_get_clean();
$slidePanelName = 'hosts/settings';
$slidePanelTitle = 'HOSTS DISPLAY SETTINGS';

include(ROOT . '/views/includes/slide-panel.inc.php');