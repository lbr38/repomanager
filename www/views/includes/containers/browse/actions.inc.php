<?php
/**
 *  The 'actions' section is only available to admins
 */
if (IS_ADMIN) : ?>
    <section class="section-right reloadable-container" container="browse/actions">

        <h3>UPLOAD PACKAGES</h3>

        <?php
        /**
         *  If a task is already running on this repo then print a message
         */
        if (!empty($rebuild) and $rebuild == 'running') : ?>
            <div class="div-generic-blue">
                <p>A task is running on this repository snapshot<img src="/assets/icons/loading.svg" class="icon" /></p>
            </div>
            <?php
        endif;

        /**
         *  If there is no task running on this repo then print action buttons
         */
        if (empty($rebuild) or (!empty($rebuild) and $rebuild != 'running')) : ?>
            <div class="div-generic-blue">
                <h5>Upload <?= $myrepo->getPackageType() ?> packages into the repository snapshot</h5>

                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="uploadPackage" />
                    <input type="hidden" name="snapId" value="<?= $snapId ?>" />

                    <h6>SELECT PACKAGES</h6>
                    <p class="note">Valid MIME types are <code>application/x-rpm</code> and <code>application/vnd.debian.binary-package</code></p>
                    <br>
                    <input type="file" name="packages[]" accept="application/vnd.debian.binary-package" multiple />
                    
                    <br><br>
                    <button type="submit" class="btn-large-green">Upload package</button>
                </form>

                <?php
                /**
                 *  Print success messages from uploading packages if there are
                 */
                if (!empty($uploadSuccessMessage)) {
                    echo '<p class="greentext">' . $uploadSuccessMessage . '</p>';
                }

                /**
                 *  Print error messages from uploading packages if there are
                 */
                if (!empty($uploadErrorMessage)) {
                    echo '<p class="redtext">' . $uploadErrorMessage . '</p>';
                } ?>
            </div>
            
            <h3>REBUILD REPO</h3>

            <?php
            $gpgSignChecked = '';

            if ($myrepo->getPackageType() == 'rpm' && RPM_SIGN_PACKAGES == 'true') {
                $gpgSignChecked = 'checked';
            }
            if ($myrepo->getPackageType() == 'deb' && DEB_SIGN_REPO == 'true') {
                $gpgSignChecked = 'checked';
            } ?>

            <div class="div-generic-blue">
                <h5>Rebuild repository snapshot metadata</h5>

                <h6>SIGN WITH GPG</h6>
                <p class="note">Signature can extend the task duration.</p>
                <label class="onoff-switch-label">
                    <input name="gpgSign" type="checkbox" class="onoff-switch-input" <?= $gpgSignChecked ?>>
                    <span class="onoff-switch-slider"></span>
                </label>

                <br><br>
                <button id="rebuildBtn" snap-id="<?= $snapId ?>" type="button" class="btn-large-red">Execute</button>
            </div>
            <?php
        endif ?>
    </section>
    <?php
endif ?>