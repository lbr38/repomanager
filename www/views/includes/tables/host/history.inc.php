<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container grid-fr-5 bck-blue-alt">
                <div>
                    <p><b><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?></b></p>
                    <p class="lowopacity-cst"><?= $item['Time']; ?>
                </div>

                <?php
                if (!empty($item['PackagesInstalled'])) : ?>
                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="installed">
                        <span class="label-green"><?= count($item['PackagesInstalled']) ?> installed</span>
                    </div>
                    <?php
                endif;

                if (!empty($item['DependenciesInstalled'])) : ?>
                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="dep-installed">
                        <span class="label-green"><?= count($item['DependenciesInstalled']) ?> dep. installed</span>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesUpdated'])) : ?>
                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="upgraded">
                        <span class="label-yellow"><?= count($item['PackagesUpdated']) ?> updated</span>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesDowngraded'])) : ?>
                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="downgraded">
                        <span class="label-red"><?= count($item['PackagesDowngraded']) ?> downgraded</span>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesRemoved'])) : ?>
                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="removed">
                        <span class="label-red"><?= count($item['PackagesRemoved']) ?> uninstalled</span>
                    </div>
                    <?php
                endif;

                if (!empty($item['PackagesPurged'])) : ?>
                    <div class="pointer event-packages-btn" host-id="<?= $id ?>" event-id="<?= $item['Id'] ?>" package-state="purged">
                        <span class="label-red"><?= count($item['PackagesPurged']) ?> uninstalled (purged)</span>
                    </div>
                    <?php
                endif ?>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
