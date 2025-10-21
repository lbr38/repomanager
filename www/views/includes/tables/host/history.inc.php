<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $date => $packageState) : ?>
            <div class="table-container-3 row-gap-15 column-gap-30 bck-blue-alt event-packages-btn pointer" host-id="<?= $id ?>" event-date="<?= $date ?>">
                <div>
                    <p><b><?= DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y') ?></b></p>
                </div>

                <div class="grid grid-rfr-1-3 row-gap-15 column-gap-30">
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

        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>
        <?php
    endif ?>
</div>
