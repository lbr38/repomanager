<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        echo '<p class="margin-top-15 margin-bottom-15">' . $reloadableTableTotalItems . ' package(s) to update</p>';

        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container-3 bck-blue-alt pointer get-package-timeline" hostid="<?= $id ?>" packagename="<?= $item['Name'] ?>" title="See package history">
                <div>
                    <?= \Controllers\Common::printProductIcon($item['Name']) ?>
                </div>

                <div>
                    <p class="copy"><?= $item['Name'] ?></p>
                    <p class="lowopacity-cst copy"><?= $item['Version'] ?></p>
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
