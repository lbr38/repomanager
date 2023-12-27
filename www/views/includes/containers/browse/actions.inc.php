<?php
/**
 *  The 'actions' section is only available to admins
 */
if (IS_ADMIN) : ?>
    <section class="section-right reloadable-container" container="browse/actions">

        <h3>UPLOAD PACKAGES</h3>

        <p>Import <?= $myrepo->getPackageType() ?> packages into the repository</p>
        <br>

        <?php
        /**
         *  If an operation is already running on this repo then print a message
         */
        if (!empty($rebuild) and $rebuild == 'running') : ?>
            <div class="div-generic-blue">
                <p>An operation is running on this repository snapshot<img src="/assets/images/loading.gif" class="icon" /></p>
            </div>
            <?php
        endif;

        /**
         *  If there is no operation running on this repo then print action buttons
         */
        if (empty($rebuild) or (!empty($rebuild) and $rebuild != 'running')) : ?>
            <div class="div-generic-blue">
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="uploadPackage" />
                    <input type="hidden" name="snapId" value="<?= $snapId ?>" />
                    <input type="file" name="packages[]" accept="application/vnd.debian.binary-package" multiple />
    
                    <p class="lowopacity-cst">Valid MIME types: 'application/x-rpm' and 'application/vnd.debian.binary-package'</p>
                    <br>
                    <button type="submit" class="btn-large-green">Add package(s)</button>
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

            <p>Rebuild repository metadata files</p>

            <br>
            <?php
                $resignChecked = '';

            if ($myrepo->getPackageType() == 'rpm' && RPM_SIGN_PACKAGES == 'true') {
                $resignChecked = 'checked';
            }
            if ($myrepo->getPackageType() == 'deb' && DEB_SIGN_REPO == 'true') {
                $resignChecked = 'checked';
            }
            ?>
            <div class="div-generic-blue">
                <div class="flex align-item-center column-gap-4">
                    <span>Sign with GPG</span>
                    <label class="onoff-switch-label">
                        <input name="reconstructGpgSign" type="checkbox" class="onoff-switch-input" <?= $resignChecked ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </div>

                <span class="lowopacity-cst">Signature can extend the operation duration</span>
                <br><br>

                <button id="reconstructBtn" snap-id="<?= $snapId ?>" type="button" class="btn-large-red"><img src="/assets/icons/rocket.svg" class="icon" />Execute</button>
            </div>
            <?php
        endif ?>
    </section>
    <?php
endif ?>