<?php ob_start(); ?>

<form id="hosts-settings" action="/hosts" method="post" autocomplete="off">
    <h6>COMPLIANCE</h6>

    <p class="note">Set the thresholds for host compliance.</p>

    <div class="flex flex-direction-column row-gap-10 margin-top-15 margin-bottom-15">
        <div>
            <p>Host is not compliant when pending updates is greater or equal to:</p>
            <input type="number" name="compliance-threshold-count" value="<?= $settings['compliance_threshold_count'] ?>" />
        </div>

        <div>
            <p>Packages to exclude from compliance checks</p>
            <p class="note">You can specify multiple packages to exclude from the compliance calculation. They will not count toward the pending updates.</p>
            <select name="compliance-exclude-packages" multiple>
                <?php
                foreach ($settings['compliance_exclude_packages'] as $package) {
                    echo '<option value="' . htmlspecialchars($package) . '" selected>' . htmlspecialchars($package) . '</option>';
                } ?>
            </select>
        </div>

        <div>
            <p>Host is not compliant when latest update is older than (days):</p>
            <input type="number" name="compliance-threshold-days" value="<?= $settings['compliance_threshold_days'] ?>" />
        </div>

        <div>
            <p>Host is not compliant when a security update is available:</p>
            <label class="onoff-switch-label">
                <input name="compliance-security-update" type="checkbox" class="onoff-switch-input" <?= $settings['compliance_security_update'] ? 'checked' : '' ?> />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>

        <div>
            <p>Host is not compliant when a reboot is required:</p>
            <label class="onoff-switch-label">
                <input name="compliance-reboot-required" type="checkbox" class="onoff-switch-input" <?= $settings['compliance_reboot_required'] ? 'checked' : '' ?> />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>
    </div>

    <button class="btn-large-green">Save</button>
</form>

<script>
    $(document).ready(function() {
        myselect2.convert('select[name="compliance-exclude-packages"]', 'Packages to exclude', true);
    });
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'hosts/settings';
$slidePanelTitle = 'HOSTS SETTINGS';

include(ROOT . '/views/includes/slide-panel.inc.php');