<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <p class="margin-top-15 margin-bottom-15 mediumopacity-cst">
        <?= $reloadableTableTotalItems ?> package<?= $reloadableTableTotalItems > 1 ? 's' : '' ?> inventored<?= $search !== '' ? ' (filtered)' : '' ?>
    </p>

    <?php
    if (empty($reloadableTableContent)) : ?>
        <p class="note"><?= $search !== '' ? 'No package matches your search.' : 'No package inventored.' ?></p>
        <?php
    else : ?>
        <div class="flex flex-direction-column row-gap-10">
            <?php
            foreach ($reloadableTableContent as $item) : ?>
                <div class="host-package-item host-package-item-installed get-package-timeline pointer" hostid="<?= $id ?>" packagename="<?= $item['Name'] ?>" packageversion="<?= $item['Version'] ?>" title="See package history">
                    <div class="flex align-item-center column-gap-10">
                        <?= \Controllers\Utils\Generate\Html\Icon::product($item['Name']); ?>

                        <div>
                            <p class="copy">
                                <?php
                                if ($item['State'] == 'removed' or $item['State'] == 'purged') {
                                    echo '<span class="redtext">' . $item['Name'] . ' (uninstalled)</span>';
                                } else {
                                    echo $item['Name'];
                                } ?>
                            </p>
                            <p class="font-size-12 lowopacity-cst copy"><?= $item['Version'] ?></p>
                        </div>
                    </div>
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
