<div class="flex item-align-center column-gap-5">
    <img src="/assets/icons/<?= $icon ?>" class="icon-np" />
    <h6 class="margin-top-0"><?= $title ?></h6>
</div>
    
<p class="note margin-bottom-15"><?= count($packages) ?> package(s)</p>

<div class="grid event-packages-details justify-space-between margin-bottom-10">
    <p><b>Time</b></p>
    <p><b>Package name</b></p>
    <p><b>Version</b></p>
    <p><b>Event</b></p>
</div>

<div class="grid event-packages-details justify-space-between">
    <?php
    $lastTime = null;

    foreach ($packages as $package) : ?>
        <p><?= $lastTime != $package['Time'] ? $package['Time'] : '' ?></p>

        <div class="flex align-item-center column-gap-5 min-width-200 pointer get-package-timeline" hostid="<?= $this->hostId ?>" packagename="<?= $package['Name'] ?>" title="See package history">
            <?= \Controllers\Common::printProductIcon($package['Name']) ?>
            <p class="copy"><?= $package['Name'] ?></p>
        </div>
        
        <p class="copy"><?= $package['Version'] ?></p>

        <p class="mediumopacity-cst">#<?= $package['Id_event'] ?></p>
        <?php
        $lastTime = $package['Time'];
    endforeach ?>
</div>
