<section class="flex-div-50 div-generic-blue reloadable-container" container="host/packages">
    <h4>PACKAGES</h4>
        
    <div class="grid grid-2">
        <div id="available-packages-btn" class="flex align-item-center column-gap-5 pointer">
            <?php
            $labelColor = 'white';

            if ($packagesAvailableTotal >= $pkgs_count_considered_critical) {
                $labelColor = 'red';
            } elseif ($packagesAvailableTotal >= $pkgs_count_considered_outdated) {
                $labelColor = 'yellow';
            } ?>

            <span class="label-<?= $labelColor ?>"><?= $packagesAvailableTotal ?></span>
            <span><b>To update</b></span>
        </div>

        <div id="installed-packages-btn" class="flex align-item-center column-gap-5 pointer">
            <span class="label-white"><?= $packagesInstalledCount ?></span>
            <span><b>Total installed</b></span>
        </div>
    </div>

    <div id="packagesContainerLoader">
        <br><br>
        <span>Loading <img src="/assets/images/loading.gif" class="icon" /></span>
    </div>

    <div id="available-packages-div">
        <?php
        /**
         *  Print available packages updates
         */
        \Controllers\Layout\Table\Render::render('host/available-packages'); ?>
    </div>

    <div id="installed-packages-div" class="hide">
        <p class="margin-top-15 margin-bottom-15"><?= count($packagesInventored) ?> packages inventored</p>

        <input type="text" id="installed-packages-search" onkeyup="filterPackage()" autocomplete="off" placeholder="Search package">

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