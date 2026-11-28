<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/edit-arch-item">
    <div class="slide-panel">
        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/edit-arch-item" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/edit-arch-item">
            <h3>EDIT <?= strtoupper($itemType) ?> <?= htmlspecialchars(strtoupper($name)) ?></h3>

            <form class="source-repo-edit-arch-item" source-id="<?= $sourceId ?>" field="<?= $field ?>" item-id="<?= $itemId ?>">
                <h6 class="required">NAME</h6>
                <input type="text" class="arch-item-param" param-name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Name" />

                <h6>DESCRIPTION</h6>
                <input type="text" class="arch-item-param" param-name="description" value="<?= htmlspecialchars($description) ?>" placeholder="Description" />

                <?php
                if ($hasEol) : ?>
                    <h6>END OF LIFE DATE</h6>
                    <input type="date" class="arch-item-param" param-name="eol" value="<?= htmlspecialchars($eol) ?>" placeholder="EOL Date" />
                    <?php
                endif ?>

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>
        </div>
    </div>
</div>
