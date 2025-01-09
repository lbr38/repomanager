<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container grid-fr-6 column-gap-30 bck-blue-alt">
                <div>
                    <p><b><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?></b></p>
                    <p class="lowopacity-cst"><?= $item['Time']; ?>
                </div>

                <?php
                if (!empty($item['PackagesInstalled'])) :
                    $title = 'INSTALLED';
                    $icon = 'check.svg';
                    $count = count($item['PackagesInstalled']); ?>

                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="installed">
                        <?php include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesReinstalled'])) :
                    $title = 'REINSTALLED';
                    $icon = 'check.svg';
                    $count = count($item['PackagesReinstalled']); ?>

                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="reinstalled">
                        <?php include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                    </div>
                    <?php
                endif;

                if (!empty($item['DependenciesInstalled'])) :
                    $title = 'DEP. INSTALLED';
                    $icon = 'check.svg';
                    $count = count($item['DependenciesInstalled']); ?>

                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="dep-installed">
                        <?php include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesUpdated'])) :
                    $title = 'UPDATED';
                    $icon = 'update-yellow.svg';
                    $count = count($item['PackagesUpdated']); ?>

                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="upgraded">
                        <?php include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesDowngraded'])) :
                    $title = 'DOWNGRADED';
                    $icon = 'rollback.svg';
                    $count = count($item['PackagesDowngraded']); ?>

                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="downgraded">
                        <?php include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesRemoved'])) :
                    $title = 'UNINSTALLED';
                    $icon = 'error.svg';
                    $count = count($item['PackagesRemoved']); ?>

                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="removed">
                        <?php include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesPurged'])) :
                    $title = 'PURGED';
                    $icon = 'error.svg';
                    $count = count($item['PackagesPurged']); ?>

                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="purged">
                        <?php include(ROOT . '/views/includes/labels/label-icon-tr.inc.php'); ?>
                    </div>
                    <?php
                endif ?>
            </div>
            <?php
            unset($title, $icon, $count);
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
