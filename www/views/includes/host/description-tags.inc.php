<?php
/**
 *  Description and tags of a host, on the host page and on its line in the hosts list ($hostLine)
 *  Edited in place by users allowed to edit hosts (see events/host/edit.js), empty elements are hidden
 *  Expects $id, $description, $tags (list), $canEditHost and $hostLine
 */

// 'editable' classes only add the pointer cursor hint (and the click-to-add placeholder) when the user is allowed to edit
$descriptionClass = $canEditHost ? 'host-description-editable' : '';
if ($canEditHost && empty($description)) {
    $descriptionClass .= ' host-description-empty';
}

$tagsClass = $canEditHost ? 'host-tags-editable' : '';
if ($canEditHost && empty($tags)) {
    $tagsClass .= ' host-tags-empty';
}

// In the hosts list the description is cut to one line, its full text is printed in the tooltip
$descriptionTitle = $hostLine ? $description : ($canEditHost ? 'Double-click to edit the description' : ''); ?>

<div class="host-description-container<?= empty($description) ? ' hide' : '' ?>">
    <p class="note host-description-input width-100 <?= $descriptionClass ?>" host-id="<?= $id ?>" title="<?= $descriptionTitle ?>"><?= $description ?></p>
</div>

<div class="host-tags-container<?= empty($tags) ? ' hide' : '' ?>">
    <div class="host-tags-display flex align-item-center flex-wrap column-gap-10 <?= $tagsClass ?>" host-id="<?= $id ?>" data-tags="<?= htmlspecialchars(implode(',', $tags)) ?>"<?= $canEditHost ? ' title="Double-click to edit tags"' : '' ?>>
        <?php
        foreach ($tags as $tag) : ?>
            <div class="flex align-item-center column-gap-5 host-tag-item"<?= $hostLine ? ' title="Click to filter by ' . htmlspecialchars($tag) . ' tag"' : '' ?>>
                <img src="/assets/icons/tag.svg" class="icon-np icon-small" />
                <span><?= htmlspecialchars($tag) ?></span>
            </div>
            <?php
        endforeach ?>
    </div>
</div>
