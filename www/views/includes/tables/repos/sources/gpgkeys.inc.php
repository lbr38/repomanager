<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container grid-fr-4-1 bck-blue-alt">
                <div>
                    <p title="Key name"><?= $item['name'] ?></p>
                    <p title="Key ID" class="lowopacity-cst copy"><?= $item['id'] ?></p>
                </div>

                <div class="flex justify-end">
                    <img src="/assets/icons/delete.svg" class="gpgKeyDeleteBtn icon-lowopacity" gpgkey-id="<?= $item['id'] ?>" gpgkey-name="<?= $item['name'] ?>" title="Delete GPG key <?= $item['name'] ?>" />
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
