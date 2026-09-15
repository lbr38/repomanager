<div class="timeline-wrapper">
    <div class="timeline-header">
        <h6>Date</h6>
        <h6>State</h6>
        <h6>Version</h6>
    </div>

    <div class="timeline">
        <?php
        foreach ($events as $event) :
            [$stateLabel, $stateClass, $stateTitle] = match ($event['State']) {
                'inventored'     => ['Inventored', 'label-tr', 'Inventored'],
                'installed'     => ['Installed', 'label-green', 'Installed'],
                'dep-installed' => ['Dependency', 'label-green', 'Installed as dependency'],
                'reinstalled'   => ['Reinstalled', 'label-green', 'Reinstalled'],
                'upgraded'      => ['Updated', 'label-yellow', 'Updated'],
                'removed'       => ['Uninstalled', 'label-red', 'Uninstalled'],
                'purged'        => ['Purged', 'label-red', 'Uninstalled (purged)'],
                'downgraded'    => ['Downgraded', 'label-yellow', 'Downgraded'],
                default         => [$event['State'], 'label-tr', $event['State']],
            }; ?>

            <div class="timeline-container timeline-state-<?= htmlspecialchars($event['State'], ENT_QUOTES) ?>">
                <div class="timeline-event">
                    <span class="timeline-pointer"></span>
                    <div class="timeline-date">
                        <p><?= DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y') ?></p>
                        <p class="lowopacity-cst"><?= $event['Time'] ?></p>
                    </div>

                    <div class="timeline-state">
                        <span class="<?= $stateClass ?>" title="<?= $stateTitle ?>"><?= $stateLabel ?></span>
                    </div>

                    <div class="timeline-version">
                        <p class="copy"><span class="label-white"><?= $event['Version'] ?></span></p>
                    </div>
                </div>
            </div>
            <?php
        endforeach ?>
    </div>
</div>
