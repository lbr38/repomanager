<?php
use \Controllers\Layout\Table\Render as TableRender; ?>

<section class="flex-div-50 div-generic-blue reloadable-container" container="host/packages">
    <div class="flex justify-space-between margin-bottom-20">
        <div>
            <h6 class="margin-top-0">PACKAGES INVENTORY</h6>
            <p class="note">Packages installed and available updates.</p>
        </div>
        
        <?php
        if (!empty($packagesInventoredTotal) or !empty($packagesAvailableTotal)) :
            $updatesOverThreshold = ($packagesAvailableTotal > 0 && $packagesAvailableTotal >= $complianceThresholdCount); ?>
            <div class="package-view-switch-wrap">
                <div class="advanced-switch-field switch-field">
                    <input type="radio" id="available-packages-switch" name="package-view" checked />
                    <label for="available-packages-switch" id="available-packages-btn" class="<?= $updatesOverThreshold ? 'package-switch-alert' : '' ?>" title="<?= $updatesOverThreshold ? $packagesAvailableTotal . ' package update(s) available, compliance threshold of ' . $complianceThresholdCount . ' reached' : $packagesAvailableTotal . ' package update(s) available' ?>">
                        <span>To update</span>
                        <strong><?= $packagesAvailableTotal ?></strong>
                    </label>

                    <input type="radio" id="installed-packages-switch" name="package-view" />
                    <label for="installed-packages-switch" id="installed-packages-btn" title="<?= $packagesInstalledCount ?> package(s) installed on this host">
                        <span>Installed</span>
                        <strong><?= $packagesInstalledCount ?></strong>
                    </label>
                </div>
            </div>
            <?php
        endif ?>
    </div>

    <?php
    if (empty($packagesInventoredTotal) and empty($packagesAvailableTotal)) : ?>
        <div class="empty-state">
            <p class="empty-state-title">Nothing for now!</p>
            <p class="note">The host did not send any packages inventory yet.</p>
        </div>
        <?php
    endif;

    if (!empty($packagesInventoredTotal) or !empty($packagesAvailableTotal)) : ?>
        <div id="packagesContainerLoader">
            <div class="flex align-item-center column-gap-10">
                <p>Loading</p>
                <img src="/assets/icons/loading.svg" class="icon" />
            </div>
        </div>

        <div id="available-packages-div">
            <?php
            // Print available packages updates
            TableRender::render('host/available-packages'); ?>
        </div>

        <div id="installed-packages-div" class="hide">
            <?php TableRender::render('host/installed-packages'); ?>
        </div>
        <?php
    endif ?>
</section>