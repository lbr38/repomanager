<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $date => $packageState) : ?>
            <div class="table-container grid-fr-6 column-gap-30 bck-blue-alt">
                <div>
                    <p><b><?= DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y') ?></b></p>
                </div>
                
                <?php
                foreach ($packageState as $state => $packages) :
                    if (empty($packages)) {
                        continue;
                    }

                    if ($state == 'installed') {
                        $title = 'INSTALLED';
                        $icon = 'check.svg';
                    }

                    if ($state == 'reinstalled') {
                        $title = 'REINSTALLED';
                        $icon = 'check.svg';
                    }

                    if ($state == 'dep-installed') {
                        $title = 'DEP. INSTALLED';
                        $icon = 'check.svg';
                    }

                    if ($state == 'upgraded') {
                        $title = 'UPDATED';
                        $icon = 'update-yellow.svg';
                    }

                    if ($state == 'downgraded') {
                        $title = 'DOWNGRADED';
                        $icon = 'rollback.svg';
                    }

                    if ($state == 'removed') {
                        $title = 'UNINSTALLED';
                        $icon = 'error.svg';
                    }
                    
                    if ($state == 'purged') {
                        $title = 'PURGED';
                        $icon = 'error.svg';
                    }

                    $count = count($packages); ?>

                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-date="<?= $date ?>" package-state="<?= $state ?>">
                        <?php include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                    </div>
                    <?php
                endforeach ?>
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
