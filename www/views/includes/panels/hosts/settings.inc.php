<?php ob_start(); ?>

<form id="hosts-settings" action="/hosts" method="post" autocomplete="off">
    <h6>COMPLIANCE THRESHOLDS</h6>

    <p class="note">Set the thresholds for host compliance.</p>

    <div class="flex flex-direction-column row-gap-10 form-block margin-top-15 margin-bottom-15">
        <div>
            <p>Host is not compliant when pending updates is greater or equal to</p>
            <input name="compliance.thresholds.count" type="number" class="settings" value="<?= $settings['compliance']['thresholds']['count'] ?>" />
        </div>

        <div>
            <p>Host is not compliant when latest update is older than (days)</p>
            <input name="compliance.thresholds.days" type="number" class="settings" value="<?= $settings['compliance']['thresholds']['days'] ?>" />
        </div>

        <div>
            <p>Host is not compliant when a security update is available</p>
            <label class="onoff-switch-label">
                <input name="compliance.thresholds.security-update" type="checkbox" class="settings onoff-switch-input" <?= $settings['compliance']['thresholds']['security-update'] ? 'checked' : '' ?> />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>

        <div>
            <p>Host is not compliant when no package update has been applied yet</p>
            <p class="note">This can happen with brand new hosts that have not applied any updates yet.</p>
            <label class="onoff-switch-label">
                <input name="compliance.thresholds.not-updated-yet" type="checkbox" class="settings onoff-switch-input" <?= $settings['compliance']['thresholds']['not-updated-yet'] ? 'checked' : '' ?> />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>

        <div>
            <p>Host is not compliant when a reboot is required</p>
            <label class="onoff-switch-label">
                <input name="compliance.thresholds.reboot-required" type="checkbox" class="settings onoff-switch-input" <?= $settings['compliance']['thresholds']['reboot-required'] ? 'checked' : '' ?> />
                <span class="onoff-switch-slider"></span>
            </label>
        </div>
    </div>

    <h6>COMPLIANCE EXCLUSIONS</h6>
    <p class="note">Specify hosts and packages that should be excluded from compliance checks.</p>

    <div class="flex flex-direction-column row-gap-10 form-block margin-top-15 margin-bottom-15">
        <div>
            <p>Hosts to exclude from compliance checks</p>
            <p class="note">You can specify multiple hosts to exclude from the compliance calculation. You can use <code>*</code> as a wildcard.</p>
            <select class="settings" name="compliance.exclusions.hosts" multiple>
                <?php
                foreach ($settings['compliance']['exclusions']['hosts'] as $host) {
                    echo '<option value="' . htmlspecialchars($host) . '" selected>' . htmlspecialchars($host) . '</option>';
                } ?>
            </select>
        </div>

        <div>
            <p>Packages to exclude from compliance checks</p>
            <p class="note">You can specify multiple packages to exclude from the compliance calculation. They will not count toward the pending updates. You can use <code>*</code> as a wildcard.</p>
            <select class="settings" name="compliance.exclusions.packages" multiple>
                <?php
                foreach ($settings['compliance']['exclusions']['packages'] as $package) {
                    echo '<option value="' . htmlspecialchars($package) . '" selected>' . htmlspecialchars($package) . '</option>';
                } ?>
            </select>
        </div>
    </div>
    <button class="btn-large-green">Save</button>
</form>

<script>
    $(document).ready(function() {
        myselect2.convert('select[name="compliance.exclusions.hosts"]', 'Hosts to exclude', true);
        myselect2.convert('select[name="compliance.exclusions.packages"]', 'Packages to exclude', true);
    });
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'hosts/settings';
$slidePanelTitle = 'HOSTS SETTINGS';

include(ROOT . '/views/includes/slide-panel.inc.php');