<?php ob_start(); ?>

<form id="host-install-packages-form" autocomplete="off">
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

    <h6 class="required">PACKAGES</h6>
    <p class="note">Specify the packages you want to install.</p>
    <div class="input-field">
        <select class="request-param" param-name="packages" name="packages" multiple></select>
    </div>

    <h6>INSTALL OPTIONS</h6>

    <h6>DRY RUN</h6>
    <p class="note">If you want to simulate the installation without actually performing it.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input request-param" value="true" param-name="dry-run" />
        <span class="onoff-switch-slider"></span>
    </label>

    <h6>IGNORE EXCLUSIONS</h6>
    <p class="note">If you want to ignore the exclusions and install packages even if they are excluded.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input request-param" value="true" param-name="ignore-exclusions" />
        <span class="onoff-switch-slider"></span>
    </label>

    <h6>KEEP CURRENT CONFIG FILES</h6>
    <p class="note">If you want to keep the current configuration files unchanged during the installation.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input request-param" value="true" param-name="keep-config-files" checked />
        <span class="onoff-switch-slider"></span>
    </label>

    <br><br>
    <button class="btn-large-red">Install</button>
</form>

<script>
$(document).ready(function(){
    myselect2.convert('select[param-name="hosts"]', 'Hosts', true);
    myselect2.convert('select[param-name="packages"]', 'Packages', true);
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'hosts/requests/install-packages';
$slidePanelTitle = 'INSTALL PACKAGES';

include(ROOT . '/views/includes/slide-panel.inc.php');