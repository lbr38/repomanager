<div class="reloadable-table margin-top-15" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <div class="flex flex-direction-column row-gap-10">
        <?php
        foreach ($reloadableTableContent as $date => $packageState) : ?>
            <div class="host-event-item event-packages-btn pointer" host-id="<?= $id ?>" event-date="<?= $date ?>">
                <div class="flex align-item-center column-gap-20">
                    <img src="/assets/icons/calendar.svg" class="icon-np lowopacity-cst" />
                    <p><b><?= DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y') ?></b></p>
                </div>

                <div class="flex align-item-center column-gap-20 flex-wrap">
                    <?php
                    foreach ($packageState as $state => $packages) :
                        if (empty($packages)) {
                            continue;
                        }

                        if ($state == 'installed') {
                            $title = 'INSTALLED';
                            $icon = 'package-installed.svg';
                        }

                        if ($state == 'reinstalled') {
                            $title = 'REINSTALLED';
                            $icon = 'package-installed.svg';
                        }

                        if ($state == 'dep-installed') {
                            $title = 'DEP. INSTALLED';
                            $icon = 'package-installed.svg';
                        }

                        if ($state == 'upgraded') {
                            $title = 'UPDATED';
                            $icon = 'package-updated.svg';
                        }

                        if ($state == 'downgraded') {
                            $title = 'DOWNGRADED';
                            $icon = 'package-updated.svg';
                        }

                        if ($state == 'removed') {
                            $title = 'UNINSTALLED';
                            $icon = 'package-removed.svg';
                        }

                        if ($state == 'purged') {
                            $title = 'PURGED';
                            $icon = 'package-removed.svg';
                        }

                        $count = count($packages);

                        include(ROOT . '/views/includes/labels/label-icon-tr.inc.php');
                    endforeach ?>
                </div>
            </div>

            <?php
        endforeach;

        unset($date, $packageState, $state, $packages, $title, $icon, $count); ?>
        </div>

        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>
        <?php
    endif ?>
</div>
