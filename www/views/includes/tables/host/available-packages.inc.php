<?php
use \Controllers\Layout\Table\Render as TableRender;
use \Controllers\Utils\Generate\Html\Icon;
use \Controllers\Utils\Convert; ?>

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
            <div class="flex align-item-center column-gap-10">
                <p class="margin-top-15 margin-bottom-15 mediumopacity-cst"><?=  $reloadableTableTotalItems ?> package<?= $reloadableTableTotalItems > 1 ? 's' : '' ?> to update</p>
                <?php
                if ($packageUpdateRunning) {
                    echo '<img src="/assets/icons/loading.svg" class="icon-np" title="A package update is running" />';
                } ?>
            </div>

            <?php
            if (IS_ADMIN) {
                // If there is no package update already running, display the select all button
                if (!$packageUpdateRunning) {
                    echo '<div class="select-all-btn btn-fit-tr align-item-center column-gap-8 pointer available-package-select-all" title="Select all packages">';
                    echo '<span>Select all</span>';
                    echo '<input type="checkbox" title="Select all packages" aria-hidden="true" tabindex="-1" />';
                    echo '</div>';
                }
            } ?>
        </div>

        <div class="flex flex-direction-column row-gap-10">
            <?php
            foreach ($reloadableTableContent as $item) :
                $checked = '';
                $excluded = false;
                $securityUpdate = Convert::toBool($item['Security'] ?? false);
                $title = 'Click to select';

                // If the package is in the "exclude on major update" list
                foreach ($packageExcludedMajor as $package) {
                    if (preg_match('#' . $package . '#', $item['Name'])) {
                        $excluded = true;
                        $title = 'This package is marked as excluded on major update in the profile configuration';
                        break;
                    }
                }

                // If the package is in the "always exclude" list
                foreach ($packageExcluded as $package) {
                    if (preg_match('#' . $package . '#', $item['Name'])) {
                        $excluded = true;
                        $title = 'This package is marked as excluded in the profile configuration';
                        break;
                    }
                } ?>

                <div class="host-package-item pointer <?= $excluded ? 'host-package-excluded' : '' ?>" title="<?= $title ?>">
                    <div class="flex align-item-center column-gap-10">
                        <?= Icon::product($item['Name']) ?>

                        <div class="get-package-timeline" hostid="<?= $id ?>" packagename="<?= $item['Name'] ?>">
                            <p class="copy" title="Available package"><?= $item['Name'] ?></p>

                            <?php
                            if (!empty($item['Repository'])) : ?>
                                <p class="note wordbreakall copy" title="Repository"><?= $item['Repository'] ?></p>
                                <?php
                            endif ?>
                        </div>
                    </div>

                    <div class="flex align-item-center column-gap-10">
                        <div class="flex align-item-center column-gap-10">
                            <?php
                            if ($securityUpdate) {
                                echo '<img src="/assets/icons/shield-warning.svg" class="icon-medium icon-np" />';
                            } ?>

                            <p class="copy" title="<?= $securityUpdate ? 'Security update available' : 'Update available' ?>">
                                <span class="label-<?= $securityUpdate ? 'yellow' : 'white' ?> wordbreakall"><?= $item['Current_version'] ?> ❯ <?= $item['Version'] ?></span>
                            </p>
                        </div>

                        <?php
                        // If package was selected, we check the checkbox
                        if (!empty($selectedPackages['packages'])) {
                            foreach ($selectedPackages['packages'] as $package) {
                                if ($package['name'] === $item['Name'] and $package['available_version'] === $item['Version']) {
                                    $checked = 'checked';
                                    break;
                                }
                            }
                        }

                        if (IS_ADMIN) {
                            // If there is no package update already running, add the checkbox
                            if ($packageUpdateRunning == false) { ?>
                                <input type="checkbox" class="available-package-checkbox <?= $excluded ? 'checkbox-warning' : '' ?>" host-id="<?= $id ?>" package="<?= $item['Name'] ?>" version="<?= $item['Version'] ?>" <?= $checked ?> <?= $excluded ? 'excluded="true"' : 'excluded="false"' ?> title="<?= $title ?>" />
                                <?php
                            }
                        } ?>
                    </div>
                </div>
                <?php
            endforeach; ?>
            
            <div class="flex justify-end margin-top-10">
                <?php TableRender::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
            </div>

            <script>
            $(function() {
                $('.available-package-checkbox:checked').closest('.host-package-item').addClass('host-package-selected');
            });
            </script>
        </div>
        <?php
    endif ?>
</div>
