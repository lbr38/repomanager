<?php ob_start(); ?>

<h6 class="margin-top-0">SIGN WITH GPG</h6>
<p class="note">Signature can extend the task duration.</p>
<label class="onoff-switch-label">
    <input name="gpgSign" type="checkbox" class="onoff-switch-input" <?= $gpgSignChecked ?>>
    <span class="onoff-switch-slider"></span>
</label>

<br><br>
<button id="snapshot-rebuild-btn" snap-id="<?= $snapId ?>" type="button" class="btn-large-red">Execute</button>

<?php
$content = ob_get_clean();
$slidePanelName = 'snapshot/rebuild';
$slidePanelTitle = 'REBUILD METADATA';

include(ROOT . '/views/includes/slide-panel.inc.php');
