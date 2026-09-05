<?php ob_start(); ?>

<form id="snapshot-upload" enctype="multipart/form-data">
    <input type="hidden" name="action" value="upload-package" />
    <input type="hidden" name="snapId" value="<?= $snapId ?>" />

    <h6 class="margin-top-0">SELECT PACKAGES TO UPLOAD</h6>
    <p class="note">Max upload size: <?= ini_get('upload_max_filesize') ?></p>
    <p class="note">Valid MIME types: <code class="font-size-11">application/x-rpm</code> and <code class="font-size-11">application/vnd.debian.binary-package</code></p>

    <br>
    <input type="file" name="packages[]" accept="application/vnd.debian.binary-package" multiple />
    
    <h6 class="">OVERWRITE EXISTING PACKAGES</h6>
    <p class="note">If a package with the same name already exists in the snapshot, it will be overwritten.</p>
    <label class="onoff-switch-label">
        <input name="overwrite" type="checkbox" class="onoff-switch-input">
        <span class="onoff-switch-slider"></span>
    </label>

    <br><br>
    <button type="submit" class="btn-large-green">Upload package</button>
</form>

<div class="margin-top-10">
    <?php
    // Print success messages from uploading packages if there are
    if (!empty($uploadSuccessMessage)) {
        echo '<p class="greentext">' . $uploadSuccessMessage . '</p>';
    }

    // Print error messages from uploading packages if there are
    if (!empty($uploadErrorDetails)) {
        foreach ($uploadErrorDetails as $errorTitle => $errorPackages) {
            echo '<p class="redtext">' . htmlspecialchars($errorTitle) . ':</p>';
            foreach ($errorPackages as $pkg) {
                echo '<p class="redtext">' . htmlspecialchars($pkg) . '</p>';
            }
        }
    }

    if (!empty($uploadErrorMessage)) {
        echo '<p class="redtext">' . $uploadErrorMessage . '</p>';
    } ?>
</div>

<?php
$content = ob_get_clean();
$slidePanelName = 'snapshot/upload';
$slidePanelTitle = 'UPLOAD PACKAGES';

include(ROOT . '/views/includes/slide-panel.inc.php');
