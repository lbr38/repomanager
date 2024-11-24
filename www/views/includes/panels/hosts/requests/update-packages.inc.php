<?php ob_start(); ?>

<form id="host-update-packages-form" autocomplete="off">
    <h6>HOSTS</h6>
    <?php
    if (empty($hosts)) {
        echo '<p class="note"><img src="/assets/icons/warning.svg" class="icon" /> Error: no host selected.</p>';
    }

    if (!empty($hosts)) : ?>
        <select class="request-param" param-name="hosts" name="hosts" multiple>
            <?php
            foreach ($hosts as $host) {
                echo '<option value="' . $host['id'] . '" selected>' . $host['hostname'] . '</option>';
            } ?>
        </select>
        <?php
    endif ?>

    <h6 class="required">UPDATE</h6>
    <p class="note">Select the type of update you want to perform.</p>
    <div class="switch-field">
        <input type="radio" id="update-all-pkg" class="request-param" param-name="update-type" name="update-type" value="all" checked />
        <label for="update-all-pkg">All packages</label>
        <input type="radio" id="update-specific-pkg" class="request-param" param-name="update-type" name="update-type" value="specific" />
        <label for="update-specific-pkg">Specific packages</label>
    </div>

    <div id="update-specific-pkg-div" class="hide">
        <h6 class="required">PACKAGES</h6>
        <p class="note">Specify the packages you want to update.</p>
        <div class="input-field">
            <select class="request-param" param-name="packages" name="packages" multiple></select>
        </div>
    </div>
    
    <h6>UPDATE OPTIONS</h6>

    <h6>DRY RUN</h6>
    <p class="note">If you want to simulate the update without actually performing it.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input request-param" value="true" param-name="dry-run" />
        <span class="onoff-switch-slider"></span>
    </label>

    <h6>IGNORE EXCLUSIONS</h6>
    <p class="note">If you want to ignore the exclusions and update packages even if they are excluded.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input request-param" value="true" param-name="ignore-exclusions" />
        <span class="onoff-switch-slider"></span>
    </label>

    <h6>FULL UPGRADE</h6>
    <p class="note">If you want to perform a full upgrade.</p>
    <p class="note">This is the same as <code>apt dist-upgrade</code> on Debian-based systems.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input request-param" value="true" param-name="full-upgrade" />
        <span class="onoff-switch-slider"></span>
    </label>

    <h6>KEEP CURRENT CONFIG FILES</h6>
    <p class="note">If you want to keep the current configuration files unchanged during the update.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input request-param" value="true" param-name="keep-config-files" checked />
        <span class="onoff-switch-slider"></span>
    </label>

    <br><br>
    <button class="btn-large-red">Update</button>
</form>

<script>
    selectToSelect2('select[param-name="hosts"]', 'Hosts', true);
    selectToSelect2('select[param-name="packages"]', 'Packages', true);
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'hosts/requests/update-packages';
$slidePanelTitle = 'UPDATE PACKAGES';

include(ROOT . '/views/includes/slide-panel.inc.php');