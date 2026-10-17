<?php
use \Controllers\Utils\Generate\Html\Icon;
use \Controllers\Utils\Convert;

$eventGroups = [];

foreach ($packages as $package) {
    // Ignore package with state 'inventored'
    if ($package['State'] == 'inventored') {
        continue;
    }

    $eventGroups[$package['Time']][] = $package;
} ?>

<div class="event-packages-list">
    <div class="event-packages-row event-packages-header">
        <h6>Time</h6>
        <h6>Package name</h6>
        <h6>Version</h6>
        <h6>State</h6>
        <h6 class="text-right">Event</h6>
    </div>

    <?php
    foreach ($eventGroups as $time => $timePackages) : ?>
        <div class="event-packages-group div-generic-blue bck-blue-alt margin-left-10 margin-right-10">
            <?php
            $firstRow = true;

            foreach ($timePackages as $package) :
                $securityUpdate = Convert::toBool($package['Security'] ?? false);

                [$stateLabel, $stateClass, $stateTitle] = match ($package['State']) {
                    'installed'     => ['Installed', 'label-green', 'Installed'],
                    'dep-installed' => ['Dependency', 'label-green', 'Installed as dependency'],
                    'reinstalled'   => ['Reinstalled', 'label-green', 'Reinstalled'],
                    'upgraded'      => ['Updated', 'label-yellow', 'Updated'],
                    'downgraded'    => ['Downgraded', 'label-yellow', 'Downgraded'],
                    'removed'       => ['Uninstalled', 'label-red', 'Uninstalled'],
                    'purged'        => ['Purged', 'label-red', 'Uninstalled (purged)'],
                    default         => [$package['State'], 'label-tr', $package['State']],
                }; ?>

                <div class="event-packages-row">
                    <p class="event-packages-time"><?= $firstRow ? $time : '' ?></p>

                    <div class="flex align-item-center column-gap-5 min-width-200 pointer get-package-timeline" hostid="<?= $this->hostId ?>" packagename="<?= $package['Name'] ?>" title="See package history">
                        <?= Icon::product($package['Name']) ?>
                        <p class="copy"><?= $package['Name'] ?></p>
                    </div>

                    <div class="flex align-item-center column-gap-10" title="<?= $securityUpdate ? 'Security update' : '' ?>">
                        <?php
                        if ($securityUpdate) {
                            echo '<img src="/assets/icons/shield-warning.svg" class="icon-medium icon-np" />';
                        } ?>

                        <p class="copy">
                            <span class="label-<?= $securityUpdate ? 'yellow' : 'white' ?>"><?= $package['Version'] ?></span>
                        </p>
                    </div>

                    <p><span class="<?= $stateClass ?>" title="<?= $stateTitle ?>"><?= $stateLabel ?></span></p>

                    <p class="text-right event-btn pointer" host-id="<?= $this->hostId ?>" event-id="<?= $package['Id_event'] ?>">#<?= $package['Id_event'] ?></p>
                </div>
                <?php
                $firstRow = false;
            endforeach ?>
        </div>
        <?php
    endforeach ?>
</div>
