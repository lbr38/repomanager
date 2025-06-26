<div class="flex item-align-center column-gap-5">
    <img src="/assets/icons/<?= $icon ?>" class="icon-np" />
    <h6 class="margin-top-0"><?= $title ?></h6>
</div>
    
<p class="note margin-bottom-15"><?= count($packages) ?> package(s)</p>
    
<div class="grid grid-2 column-gap-10 row-gap-6 justify-space-between">
    <?php
    foreach ($packages as $package) : ?>
        <div class="flex align-item-center column-gap-5 min-width-200 pointer get-package-timeline" hostid="<?= $this->hostId ?>" packagename="<?= $package['Name'] ?>" title="See package history">
            <?= \Controllers\Common::printProductIcon($package['Name']) ?>
            <p class="copy"><?= $package['Name'] ?></p>
        </div>
        
        <p class="copy"><?= $package['Version'] ?></p>
        <?php
    endforeach ?>
</div>
