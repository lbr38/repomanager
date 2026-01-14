<div class="reloadable-table div-generic-blue" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="flex flex-wrap justify-space-between">
        <div>
            <h6 class="margin-top-0">TOP IP ACCESS</h6>
            <p class="note">Number of accesses to the repository snapshot by IP address for the selected date.</p>
        </div>

        <input id="ip-access-date-input" type="date" class="input-medium" value="<?= $date ?>" />
    </div>

    <?php
    if (empty($reloadableTableContent)) {
        echo '<p class="note">Nothing for now!</p>';
    }

    if (!empty($reloadableTableContent)) : ?>
        <div class="margin-top-5">
            <?php
            foreach ($reloadableTableContent as $item) : ?>
                <div class="table-container grid-2 justify-space-between align-item-start bck-blue-alt">
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
