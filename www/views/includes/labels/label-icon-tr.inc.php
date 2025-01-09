<div class="label-icon-tr max-width-fit">
    <img src="/assets/icons/<?= $icon ?>" class="icon-np" />
    <div class="flex flex-direction-column raw-gap-2">
        <p class="font-size-13"><?= $title ?></p>
        <?php
        if (isset($count)) : ?>
            <p class="font-size-13 mediumopacity-cst"><?= $count ?></p>
            <?php
        endif ?>
    </div>
</div>