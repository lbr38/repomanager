<?php
if ($myrepo->getPackageType() == 'rpm') {
    $mirror = $myrepo->getName();
}
if ($myrepo->getPackageType() == 'deb') {
    $mirror = $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection();
} ?>

<tr>
    <td colspan="100%">Task will create a new mirror snapshot:
    <br><br><span class="label-white"><?= $mirror ?></span>⟶<span class="label-green"><?= DATE_DMY ?></span><span id="update-repo-show-target-env-<?= $myrepo->getSnapId() ?>"></span></td>
</tr>

<tr>
    <td colspan="100%"><b>Update parameters</b></td>
</tr>

<tr>
    <td class="td-30" title="Selected snapshot content will be copied to the new snapshot before syncing packages. Then only the new changed packages will be synced from source repository. Can significantly reduce syncing duration on large repos.">Only sync the difference</td>
    <td>
        <label class="onoff-switch-label">
            <input type="checkbox" class="onoff-switch-input task-param" value="true" param-name="only-sync-difference" checked />
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>

<tr>
    <td class="td-30">Architecture</td>
    <td>
        <select class="task-param" param-name="arch" multiple>
            <option value="">Select architecture...</option>
            <?php
            if ($myrepo->getPackageType() == 'rpm') :
                foreach (RPM_ARCHS as $arch) {
                    if (in_array($arch, $myrepo->getArch())) {
                        echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                    } else {
                        echo '<option value="' . $arch . '">' . $arch . '</option>';
                    }
                }
            endif;
            if ($myrepo->getPackageType() == 'deb') :
                foreach (DEB_ARCHS as $arch) {
                    if (in_array($arch, $myrepo->getArch())) {
                        echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                    } else {
                        echo '<option value="' . $arch . '">' . $arch . '</option>';
                    }
                }
            endif; ?>
        </select>
    </td>
</tr>

<tr>
    <td class="td-30">Point an environment</td>
    <td>
        <select id="update-repo-target-env-select-<?= $myrepo->getSnapId() ?>" class="task-param" param-name="env">
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

<tr>
    <td colspan="100%"><b>GPG parameters</b></td>
</tr>

<tr>
    <td class="td-30">Check GPG signatures</td>
    <td>
        <label class="onoff-switch-label">
            <input type="checkbox" param-name="gpg-check" class="onoff-switch-input task-param" value="true" checked />
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>

<tr>
    <td class="td-30">Sign with GPG</td>
    <td>
        <label class="onoff-switch-label">
            <?php
            if ($myrepo->getPackageType() == 'rpm') : ?>
                <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param type_rpm" value="true" <?php echo (RPM_SIGN_PACKAGES == "true") ? 'checked' : ''; ?>>
                <?php
            endif;

            if ($myrepo->getPackageType() == 'deb') : ?>
                <input type="checkbox" param-name="gpg-sign" class="onoff-switch-input task-param type_deb" value="true" <?php echo (DEB_SIGN_REPO == "true") ? 'checked' : ''; ?>>
                <?php
            endif ?>
            <span class="onoff-switch-slider"></span>
        </label>
    </td>
</tr>

<?php
/**
 *  Define schedule form action and allowed type(s)
 */
$scheduleForm['action'] = 'update';
$scheduleForm['type'] = array('unique', 'recurring'); ?>

<script>
$(document).ready(function(){
    /**
     *  Convert select to select2
     */
    selectToSelect2('select.task-param[param-name="arch"]');

    /**
     *  Update repo->date<-env schema if an env is selected
     */
    var selectName = '#update-repo-target-env-select-<?= $myrepo->getSnapId() ?>';
    var envSpan = '#update-repo-show-target-env-<?= $myrepo->getSnapId() ?>';

    function printEnv() {
        /**
         *  Name of the last environment of the chain
         */
        var lastEnv = '<?= LAST_ENV ?>';

        /**
         *  Retrieve the selected environment in the list
         */
        var selectValue = $(selectName).val();
        
        /**
         *  If the environment corresponds to the last environment of the chain then it will be displayed in red
         */
        if (selectValue == lastEnv) {
            var envSpanClass = 'last-env';
        } else {            
            var envSpanClass = 'env';
        }

        /**
         *  If there is no environment selected by the user then nothing is displayed
         */
        if (selectValue == "") {
            $(envSpan).html('');
        
        /**
         *  Else we display the environment that points to the new snapshot that will be created
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