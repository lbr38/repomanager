<section class="flex-div-50 div-generic-blue reloadable-container" container="host/packages">
    <h6 class="margin-top-0">PACKAGES INVENTORY</h6>
    <p class="note">Packages installed and available updates.</p>
    
    <?php
    if (empty($packagesInventoredTotal) and empty($packagesAvailableTotal)) : ?>
        <div class="empty-state">
            <p class="empty-state-title">Nothing for now!</p>
            <p class="note">The host did not send any packages inventory yet.</p>
        </div>
        <?php
    endif;

    if (!empty($packagesInventoredTotal) or !empty($packagesAvailableTotal)) : ?>
        <div class="flex align-item-center column-gap-40 margin-top-15 margin-bottom-15">
            <div>
                <div id="installed-packages-btn" class="pointer">
                    <?php
                    $title = 'INSTALLED';
                    $count = $packagesInstalledCount;
                    $icon = 'check.svg';
                    include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                </div>
            </div>

            <div>           
                <div id="available-packages-btn" class="pointer">
                    <?php
                    $title = 'TO UPDATE';
                    $count = $packagesAvailableTotal;
                    if ($packagesAvailableTotal >= $complianceThresholdCount) {
                        $icon = 'update-red.svg';
                    }
                    include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                </div>
            </div>
        </div>

        <div id="packagesContainerLoader">
            <div class="flex align-item-center column-gap-10">
                <p>Loading</p>
                <img src="/assets/icons/loading.svg" class="icon" />
            </div>
        </div>

        <div id="available-packages-div">
            <?php
            // Print available packages updates
            \Controllers\Layout\Table\Render::render('host/available-packages'); ?>
        </div>

        <div id="installed-packages-div" class="hide">
            <input type="text" id="installed-packages-search" class="margin-bottom-5" autocomplete="off" placeholder="Search package" value="<?= !empty($_COOKIE['tables/host/installed-packages/search']) ? htmlspecialchars($_COOKIE['tables/host/installed-packages/search'], ENT_QUOTES) : '' ?>">

            <?php \Controllers\Layout\Table\Render::render('host/installed-packages'); ?>
        </div>
        <?php
    endif ?>
</section>