<?php
$lastTime = null;

foreach ($packages as $package) :
    // Ignore package with state 'inventored'
    if ($package['State'] == 'inventored') {
        continue;
    }

    // Close previous time block if time changed
    if (!is_null($lastTime) && $lastTime != $package['Time']) {
        echo '</div>';
    }

    // Create a new time block if time changed
    if ($lastTime != $package['Time']) : ?>
        <div class="grid event-packages-details justify-space-between margin-bottom-10 div-generic-blue bck-blue-alt">
        <p><b>Time</b></p>
        <p><b>Package name</b></p>
        <p><b>Version</b></p>
        <p><b>State</b></p>
        <p class="text-right"><b>Event</b></p>
        <?php
    endif ?>

    <p><?= $lastTime != $package['Time'] ? $package['Time'] : '' ?></p>

    <div class="flex align-item-center column-gap-5 min-width-200 pointer get-package-timeline" hostid="<?= $this->hostId ?>" packagename="<?= $package['Name'] ?>" title="See package history">
        <?= \Controllers\Utils\Generate\Html\Icon::product($package['Name']) ?>
        <p class="copy"><?= $package['Name'] ?></p>
    </div>
    
    <p class="copy"><?= $package['Version'] ?></p>

    <div>
        <?php
        if ($package['State'] == 'installed') {
            $title = 'Installed';
            $icon = 'package-installed';
        }
        if ($package['State'] == 'reinstalled') {
            $title = 'Reinstalled';
            $icon = 'package-installed';
        }
        if ($package['State'] == 'dep-installed') {
            $title = 'Installed as dependency';
            $icon = 'package-installed';
        }
        if ($package['State'] == 'upgraded') {
            $title = 'Updated';
            $icon = 'package-updated';
        }
        if ($package['State'] == 'removed') {
            $title = 'Uninstalled';
            $icon = 'package-removed';
        }
        if ($package['State'] == 'purged') {
            $title = 'Purged';
            $icon = 'package-removed';
        }
        if ($package['State'] == 'downgraded') {
            $title = 'Downgraded';
            $icon = 'package-updated';
        } ?>

        <img src="/assets/icons/<?= $icon ?>.svg" class="icon-np tooltip" title="<?= $title ?>" />
    </div>

    <p class="text-right event-btn pointer" host-id="<?= $this->hostId ?>" event-id="<?= $package['Id_event'] ?>">#<?= $package['Id_event'] ?></p>

    <?php
    $lastTime = $package['Time'];
endforeach ?>
