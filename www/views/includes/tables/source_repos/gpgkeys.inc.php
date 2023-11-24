<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <p>Imported GPG keys</p>
        <br>

        <?php
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
        
        <div class="flex column-gap-10 justify-end">
            <?php
            if ($reloadableTableOffset > 0) {
                echo '<div class="reloadable-table-previous-btn btn-small-green">Previous</div>';
            }

            if ($reloadableTableCurrentPage < $reloadableTableTotalPages) {
                echo '<div class="reloadable-table-next-btn btn-small-green">Next</div>';
            } ?>
        </div>

        <?php
    endif ?>
</div>
