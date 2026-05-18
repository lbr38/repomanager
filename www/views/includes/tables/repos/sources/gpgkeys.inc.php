<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <h6>CURRENT GPG SIGNING KEYS</h6>

    <p class="note">All keys imported in Repomanager keyring.</p>

    <?php
    if (empty($reloadableTableContent)) : ?>
        <div class="empty-state">
            <p class="empty-state-title">No GPG key imported yet.</p>
            <p class="note">Keys are imported automatically when source repository lists define fingerprints or key URLs.</p>
            <div class="empty-state-actions">
                <button type="button" class="btn-medium-blue get-panel-btn" panel="repos/sources/import">Import sources</button>
            </div>
        </div>
        <?php
    endif;

    if (!empty($reloadableTableContent)) : ?>
        <div class="flex justify-end margin-bottom-10 margin-right-15">
            <input type="checkbox" class="select-all-checkbox lowopacity" checkbox-id="gpg-key" title="Select all GPG keys" />
        </div>

        <?php
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container grid-fr-4-1 bck-blue-alt">
                <div>
                    <p title="Key name"><?= $item['name'] ?></p>
                    <p title="Key ID" class="lowopacity-cst copy"><?= $item['id'] ?></p>
                </div>

                <div class="flex justify-end">
                    <input type="checkbox" class="child-checkbox lowopacity" checkbox-id="gpg-key" checkbox-data-attribute="gpg-key-id" gpg-key-id="<?= $item['id'] ?>" title="Select GPG key <?= $item['name'] ?>" />
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
