<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        /**
         *  If a cookie exists with the selected packages, we load it
         *  This is used to keep the selected packages after a table page change
         */
        if (!empty($_COOKIE['temp/host-av-package-selected'])) {
            $selectedPackages = json_decode($_COOKIE['temp/host-av-package-selected'], true);
        } ?>

        <div class="flex justify-space-between align-item-center">
            <p class="margin-top-15 margin-bottom-15 mediumopacity-cst"><?=  $reloadableTableTotalItems ?> package(s) to update</p>

            <div class="margin-right-20">
                <?php
                if (IS_ADMIN) {
                    // If there is no package update already running, display the select all checkbox
                    if ($packageUpdateRunning === false) {
                        echo '<input type="checkbox" class="available-package-select-all lowopacity" title="Select all packages" />';
                    } else {
                        echo '<img src="/assets/icons/loading.svg" class="icon-np" title="A package update is already running" />';
                    }
                } ?>
            </div>
        </div>

        <?php
        foreach ($reloadableTableContent as $item) :
            $checked = ''; ?>

            <div class="table-container-3 bck-blue-alt">
                <div>
                    <?= \Controllers\Common::printProductIcon($item['Name']) ?>
                </div>

                <div class="get-package-timeline pointer" hostid="<?= $id ?>" packagename="<?= $item['Name'] ?>" title="See package history">
                    <p class="copy"><?= $item['Name'] ?></p>
                    <p class="lowopacity-cst copy"><?= $item['Version'] ?></p>
                </div>

                <div class="text-right margin-right-5">
                    <?php
                    /**
                     *  If package was selected, we check the checkbox
                     */
                    if (!empty($selectedPackages['packages'])) {
                        foreach ($selectedPackages['packages'] as $package) {
                            if ($package['name'] === $item['Name'] and $package['available_version'] === $item['Version']) {
                                $checked = 'checked';
                                break;
                            }
                        }
                    } ?>

                    <?php
                    if (IS_ADMIN) {
                        // If there is no package update already running, display the checkbox
                        if ($packageUpdateRunning == false) { ?>
                            <input type="checkbox" class="available-package-checkbox lowopacity" host-id="<?= $id ?>" package="<?= $item['Name'] ?>" version="<?= $item['Version'] ?>" <?= $checked ?> title="Select package" />
                            <?php
                        }
                    } ?>
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
