<?php ob_start(); ?>

<div class="flex align-item-center column-gap-4">
    <label class="onoff-switch-label">
        <input type="hidden" name="printRepoSize" value="off" />
        <input class="onoff-switch-input" type="checkbox" name="printRepoSize" value="on" <?php echo (PRINT_REPO_SIZE == "yes") ? 'checked' : ''; ?>>
        <span class="onoff-switch-slider"></span>
    </label>
    <span>Print repo size</span>
</div>

<div class="flex align-item-center column-gap-4">
    <label class="onoff-switch-label">
        <input type="hidden" name="printRepoType" value="off" />
        <input class="onoff-switch-input" type="checkbox" name="printRepoType" value="on" <?php echo (PRINT_REPO_TYPE == "yes") ? 'checked' : ''; ?>>
        <span class="onoff-switch-slider"></span>
    </label>
    <span>Print repo type (mirror / local)</span><br>
</div>

<div class="flex align-item-center column-gap-4">
    <label class="onoff-switch-label">
        <input type="hidden" name="printRepoSignature" value="off" />
        <input class="onoff-switch-input" type="checkbox" name="printRepoSignature" value="on" <?php echo (PRINT_REPO_SIGNATURE == "yes") ? 'checked' : ''; ?>>
        <span class="onoff-switch-slider"></span>
    </label>
    <span> Print repo or packages GPG signature</span>
</div>

<br>
<br>
<button id="repos-display-conf-btn" type="submit" class="btn-large-green">Save</button>

<?php
$content = ob_get_clean();
$slidePanelName = 'display';
$slidePanelTitle = 'REPOS LIST DISPLAY SETTINGS';

include(ROOT . '/views/includes/slide-panel.inc.php');
