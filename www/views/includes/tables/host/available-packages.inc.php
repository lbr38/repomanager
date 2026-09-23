<?php
use \Controllers\Layout\Table\Render as TableRender;
use \Controllers\Utils\Generate\Html\Icon;
use \Controllers\Utils\Convert; ?>

<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (empty($reloadableTableContent)) : ?>
        <div class="empty-state">
            <p class="empty-state-title">No available updates</p>
            <p class="note">There are no available package updates at the moment.</p>
        </div>
        <?php
    endif;

    if (!empty($reloadableTableContent)) : ?>
        <div class="flex justify-space-between align-item-center">
            <div class="flex flex-wrap align-item-center column-gap-10 row-gap-5 margin-top-15 margin-bottom-15">
                <p class=" mediumopacity-cst"><?=  $reloadableTableTotalItems ?> package<?= $reloadableTableTotalItems > 1 ? 's' : '' ?> to update</p>

                <?php
                if ($securityUpdates > 0) {
                    echo '<span class="label-yellow label-medium">' .  $securityUpdates . ' security update' . ($securityUpdates > 1 ? 's' : '') . '</span>';
                }

                if ($regularUpdates > 0) {
                    echo '<span class="label-white label-medium">' .  $regularUpdates . ' regular update' . ($regularUpdates > 1 ? 's' : '') . '</span>';
                }

                if ($packageUpdateRunning) {
                    echo '<img src="/assets/icons/loading.svg" class="icon-np" title="A package update is running" />';
                } ?>
            </div>

            <?php
            // If there is no package update already running, display the select all button
            if (IS_ADMIN and !$packageUpdateRunning) : ?>
                <div class="select-all-btn btn-fit-tr align-item-center column-gap-8 pointer available-package-select-all" title="Select all packages">
                    <span>Select all</span>
                    <input type="checkbox" title="Select all packages" aria-hidden="true" tabindex="-1" />
                </div>
                <?php
            endif ?>
        </div>

        <div class="flex flex-direction-column row-gap-10">
            <?php
            foreach ($reloadableTableContent as $item) :
                $checked = '';
                $class = '';
                $title = 'Regular update, click to select';
                $excluded = false;
                $securityUpdate = Convert::toBool($item['Security'] ?? false);

                if ($securityUpdate) {
                    $class = 'accent-yellow';
                    $title = 'Security update, click to select';
                }



                // If the package is in the "exclude on major update" list
                foreach ($packageExcludedMajor as $package) {
                    if (preg_match('#' . $package . '#', $item['Name'])) {
                        $excluded = true;
                        $class .= ' host-package-excluded';
                        $title = 'This package is marked as excluded on major update in the profile configuration, click to select anyway';
                        break;
                    }
                }

                // If the package is in the "always exclude" list
                foreach ($packageExcluded as $package) {
                    if (preg_match('#' . $package . '#', $item['Name'])) {
                        $excluded = true;
                        $class .= ' host-package-excluded';
                        $title = 'This package is marked as excluded in the profile configuration, click to select anyway';
                        break;
                    }
                }

                if ($packageUpdateRunning) {
                    $class .= ' host-package-update-running nopointer';
                    $title = 'An update is currently running, please wait until it finishes';
                } ?>

                <div class="host-package-item grid grid-rfr-1-2 pointer <?= $class ?>" title="<?= $title ?>">
                    <div class="flex align-item-center column-gap-10">
                        <?= Icon::product($item['Name']) ?>

                        <div class="get-package-timeline" hostid="<?= $id ?>" packagename="<?= $item['Name'] ?>">
                            <p class="copy pointer" title="Click to see <?= $item['Name'] ?> package timeline"><?= $item['Name'] ?></p>

                            <?php
                            if (!empty($item['Repository'])) : ?>
                                <p class="note wordbreakall copy" title="Repository: <?= $item['Repository'] ?>"><?= $item['Repository'] ?></p>
                                <?php
                            endif ?>
                        </div>
                    </div>

                    <div class="flex align-item-center justify-end column-gap-10 min-width-0">
                        <div class="host-package-versions copy flex relative padding-left-30 min-width-0" title="<?= $securityUpdate ? 'Security update available' : 'Update available' ?>: <?= $item['Current_version'] ?> ❯ <?= $item['Version'] ?>">
                            <span class="label-white text-ellipsis"><?= $item['Current_version'] ?> ❯ <?= $item['Version'] ?></span>
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
                            if (!$packageUpdateRunning) { ?>
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
