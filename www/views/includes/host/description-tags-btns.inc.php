<?php
/**
 *  "Add a description" / "Add tags" buttons of a host, hidden when the host already has some
 *  In the hosts list ($hostLine) they are icons only revealed when hovering the host line, to keep the hostname readable
 *  Expects $description, $tags (list), $canEditHost and $hostLine
 */
if ($canEditHost) : ?>
    <div class="host-add-description-btn<?= !empty($description) ? ' hide' : '' ?>" title="Add a description">
        <img src="/assets/icons/<?= $hostLine ? 'edit' : 'plus' ?>.svg" class="icon-np icon-small" />
        <?php
        if (!$hostLine) : ?>
            <span>Description</span>
            <?php
        endif ?>
    </div>

    <div class="host-add-tags-btn<?= !empty($tags) ? ' hide' : '' ?>" title="Add tags">
        <img src="/assets/icons/<?= $hostLine ? 'tag' : 'plus' ?>.svg" class="icon-np icon-small" />
        <?php
        if (!$hostLine) : ?>
            <span>Tags</span>
            <?php
        endif ?>
    </div>
    <?php
endif;
