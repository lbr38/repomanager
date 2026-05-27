<?php ob_start(); ?>

<form id="hosts-settings" action="/hosts" method="post" autocomplete="off">
    <h6>COMPLIANCE</h6>

    <p class="note">Set the thresholds for host compliance.</p>

    <div class="flex flex-direction-column row-gap-10 margin-top-15 margin-bottom-15">
        <div>
            <p>Host is not compliant when pending updates is greater or equal to:</p>
            <input type="number" name="compliance-threshold-count" value="<?= $complianceThresholdCount ?>" />
        </div>

        <div>
            <p>Host is not compliant when latest update is older than (days):</p>
            <input type="number" name="compliance-threshold-days" value="<?= $complianceThresholdDays ?>" />
        </div>

        <div>
            <p>Host is not compliant when a security update is available:</p>
            <label class="onoff-switch-label">
                <input name="compliance-security-update" type="checkbox" class="onoff-switch-input" <?= $complianceSecurityUpdate ? 'checked' : '' ?> />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>

        <div>
            <p>Host is not compliant when a reboot is required:</p>
            <label class="onoff-switch-label">
                <input name="compliance-reboot-required" type="checkbox" class="onoff-switch-input" <?= $complianceRebootRequired ? 'checked' : '' ?> />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>
    </div>

    <button class="btn-large-green">Save</button>
</form>

<?php
$content = ob_get_clean();
$slidePanelName = 'hosts/settings';
$slidePanelTitle = 'HOSTS SETTINGS';

include(ROOT . '/views/includes/slide-panel.inc.php');