<?php
/**
 *  The 'actions' section is only available to admins
 */
if (IS_ADMIN) : ?>
    <section class="mainSectionRight">

        <h3>UPLOAD PACKAGES</h3>

        <p>Import <?= $myrepo->getPackageType() ?> packages into the repository</p>
        <br>

        <?php
        if ($pathError == 0) {
            /**
             *  If an operation is already running on this repo then print a message
             */
            if (!empty($reconstruct) and $reconstruct == 'running') : ?>
                <div class="div-generic-blue">
                    <p>An operation is running on this repo<img src="resources/images/loading.gif" class="icon" /></p>
                </div>
                <?php
            endif;

            /**
             *  If there is no operation running on this repo then print action buttons
             */
            if (empty($reconstruct) or (!empty($reconstruct) and $reconstruct != 'running')) : ?>
                <div class="div-generic-blue">
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="uploadPackage" />
                        <input type="file" name="packages[]" accept="application/vnd.debian.binary-package" multiple />
                        <p class="lowopacity">Valid MIME types: 'application/x-rpm' and 'application/vnd.debian.binary-package'</p>
                        <br>
                        <button type="submit" class="btn-large-green">Add package(s)</button>
                    </form>

                    <?php
                    /**
                     *  Print error messages from uploading packages if there are
                     */
                    if (!empty($packageExists)) {
                        echo '<br><span class="redtext">Following packages already exist and have not been uploaded: <b>' . rtrim($packageExists, ', ') . '</b></span>';
                    }
                    if (!empty($packagesError)) {
                        echo '<br><span class="redtext">Following packages encountered error and have not been uploaded: <b>' . rtrim($packagesError, ', ') . '</b></span>';
                    }
                    if (!empty($packageEmpty)) {
                        echo '<br><span class="redtext">Following packages are empty and have not been uploaded: <b>' . rtrim($packageEmpty, ', ') . '</b></span>';
                    }
                    if (!empty($packageInvalid)) {
                        echo '<br><span class="redtext">Following packages are invalid and have not been uploaded: <b>' . rtrim($packageInvalid, ', ') . '</b></span>';
                    } ?>
                </div>
                
                <h3>REBUILD REPO</h3>

                <p>Rebuild repository metadata files</p>
                <br>

                <div class="div-generic-blue">
                    <form id="hidden-form" action="" method="post">
                        <input type="hidden" name="action" value="reconstruct">
                        <input type="hidden" name="snapId" value="<?= $snapId ?>">
                        <span>Sign with GPG </span>
                        <label class="onoff-switch-label">
                            <?php
                            $resignChecked = '';

                            if ($myrepo->getPackageType() == "rpm") {
                                if (RPM_SIGN_PACKAGES == 'yes') {
                                    $resignChecked = 'checked';
                                }
                            }
                            if ($myrepo->getPackageType() == "deb") {
                                if (DEB_SIGN_REPO == 'yes') {
                                    $resignChecked = 'checked';
                                }
                            } ?>
                            <input name="repoGpgResign" type="checkbox" class="onoff-switch-input" value="yes" <?= $resignChecked ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                        <span class="graytext">  (Signature can extend the operation duration)</span>
                        <br><br>
                        <button type="submit" class="btn-large-red"><img src="resources/icons/rocket.svg" class="icon" />Execute</button>
                    </form>
                </div>
                <?php
            endif;
        } else {
            echo '<p>You can\'t execute any actions.</p>';
        } ?>
    </section>
    <?php
endif ?>

<section class="mainSectionLeft">
    <h3>BROWSE</h3>

    <?php
    if ($pathError !== 0) {
        echo '<p>Error: specified repo does not exist.</p>';
    }

    if ($pathError === 0) {
        if (!empty($myrepo->getName()) and !empty($myrepo->getDist()) and !empty($myrepo->getSection())) {
            echo '<p>Explore <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span></p>';
        } else {
            echo '<p>Explore <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span></p>';
        }
    } ?>

    <br>

    <div class="div-generic-blue">
        <?php
        if ($myrepo->getReconstruct() == 'needed' or (is_dir($repoPath . '/my_uploaded_packages') and !Controllers\Common::dirIsEmpty($repoPath . "/my_uploaded_packages"))) {
            echo '<span class="yellowtext">This snapshot content has been modified. You have to rebuild metadata.</span>';
        } ?>
        <span id="loading">Generating tree structure<img src="resources/images/loading.gif" class="icon" /></span>

        <div id="explorer" class="hide">
            <?php
            /**
             *  Build packages list if there is no error
             */
            if ($pathError == 0) : ?>
                <form action="" method="post">
                    <?php
                    if (IS_ADMIN) : ?>
                        <input type="hidden" name="action" value="deletePackages" />
                        <input type="hidden" name="snapId" value="<?= $snapId ?>" />
                        <span id="delete-packages-btn" class="hide">
                            <button type="submit" class="btn-medium-red">Delete</button>
                        </span>
                        <br>
                        <?php
                    endif;

                    /**
                     *  Print packages that could not be deleted if there are
                     */
                    if (!empty($packagesToDeleteNonExists)) {
                        echo '<br><span class="redtext">Following packages does not exist and have not been deleted: <b>' . rtrim($packagesToDeleteNonExists, ', ') . '</b></span>';
                    }

                    /**
                     *  Print packages that have been deleted if there are
                     */
                    if (!empty($packagesDeleted)) {
                        echo '<br><span class="greentext">Following packages have been deleted:</span>';
                        foreach ($packagesDeleted as $packageDeleted) {
                            echo '<br><span class="greentext"><b>' . $packageDeleted . '</b></span>';
                        }
                        unset($packagesDeleted, $packageDeleted);
                    }

                    /**
                     *  Packages list
                     */
                    \Controllers\Browse::tree($repoPath); ?>
                </form>
                <?php
            endif; ?>
        </div>
    </div>
</section>