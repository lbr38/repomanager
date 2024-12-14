<section class="flex-div-50 div-generic-blue reloadable-container" container="host/packages">
    <h6 class="margin-top-0">PACKAGES INVENTORY</h6>
    <p class="note">Packages installed and available updates.</p>
        
    <div class="grid grid-2 margin-bottom-15">
        <div>
            <h6>INSTALLED</h6>
            <span id="installed-packages-btn" class="label-white pointer"><?= $packagesInstalledCount ?></span>
        </div>

        <div>
            <h6>TO UPDATE</h6>

            <?php
            $labelColor = 'white';

            if ($packagesAvailableTotal >= $pkgs_count_considered_critical) {
                $labelColor = 'red';
            } elseif ($packagesAvailableTotal >= $pkgs_count_considered_outdated) {
                $labelColor = 'yellow';
            } ?>

            <span id="available-packages-btn" class="label-<?= $labelColor ?> pointer"><?= $packagesAvailableTotal ?></span>
        </div>
    </div>

    <div id="packagesContainerLoader">
        <br><br>
        <span>Loading <img src="/assets/icons/loading.svg" class="icon" /></span>
    </div>

    <div id="available-packages-div">
        <?php
        /**
         *  Print available packages updates
         */
        \Controllers\Layout\Table\Render::render('host/available-packages'); ?>
    </div>

    <div id="installed-packages-div" class="hide">
        <p class="mediumopacity-cst margin-top-15 margin-bottom-15"><?= count($packagesInventored) ?> packages inventored</p>

        <input type="text" id="installed-packages-search" class="margin-bottom-5" onkeyup="filterPackage()" autocomplete="off" placeholder="Search package">

        <div id="installed-packages-container">
            <?php
            if (!empty($packagesInventored)) :
                foreach ($packagesInventored as $item) : ?>
                    <div class="table-container-3 bck-blue-alt pointer package-row get-package-timeline" hostid="<?= $id ?>" packagename="<?= $item['Name'] ?>" packageversion="<?= $item['Version'] ?>" title="See package history">
                        <div>
                            <?= \Controllers\Common::printProductIcon($item['Name']);?>
                        </div>

                        <div>
                            <p class="copy">
                                <?php
                                /**
                                 *  If package is removed or purged, show it in red
                                 */
                                if ($item['State'] == 'removed' or $item['State'] == 'purged') {
                                    echo '<span class="redtext">' . $item['Name'] . ' (uninstalled)</span>';
                                } else {
                                    echo $item['Name'];
                                } ?>
                            </p>
                            <p class="lowopacity-cst copy"><?= $item['Version'] ?></p>
                        </div>
                    </div>
                    <?php
                endforeach;
            endif ?>
        </div>
    </div>
</section>