<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (empty($reloadableTableContent)) {
        echo '<p class="note">Nothing for now!</p>';
    }

    if (!empty($reloadableTableContent)) : ?>
        <div class="flex flex-direction-column row-gap-10 margin-top-5">
            <?php
            foreach ($reloadableTableContent as $item) : ?>
                <div class="stats-access-item grid-2 justify-space-between">
                    <div>
                        <p class="copy"><?= htmlspecialchars($item['Source']) ?></p>
                        <p class="lowopacity-cst copy"><?= htmlspecialchars($item['IP']) ?></p>
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
