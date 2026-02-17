<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (empty($reloadableTableContent)) {
        echo '<p class="note">Nothing for now!</p>';
    }

    if (!empty($reloadableTableContent)) : ?>
        <div class="margin-top-5">
            <?php
            foreach ($reloadableTableContent as $item) : ?>
                <div class="table-container grid-2 justify-space-between align-item-center bck-blue-alt">
                    <div>
                        <p class="copy"><?= $item['Source'] ?></p>
                        <p class="lowopacity-cst copy"><?= $item['IP'] ?></p>
                    </div>

                    <p><?= $item['Count'] ?></p>
                </div>
                <?php
            endforeach; ?>
        </div>
        
        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
