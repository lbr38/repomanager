<?php
use \Controllers\Utils\Convert;
use \Controllers\Utils\Generate\Html\Label; ?>

<div class="timeline-wrapper">
    <div class="timeline-header">
        <h6>Date</h6>
        <h6>Version</h6>
        <h6>State</h6>
    </div>

    <div class="timeline">
        <?php
        foreach ($events as $event) :
            $securityUpdate = Convert::toBool($event['Security'] ?? false); ?>

            <div class="timeline-container timeline-state-<?= htmlspecialchars($event['State'], ENT_QUOTES) ?>">
                <div class="timeline-event">
                    <span class="timeline-pointer"></span>
                    <div class="timeline-date">
                        <p><?= DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y') ?></p>
                        <p class="lowopacity-cst"><?= $event['Time'] ?></p>
                    </div>

                    <div class="flex align-item-center column-gap-10" title="<?= $securityUpdate ? 'Security update' : 'Regular update' ?>">
                        <p class="copy">
                            <span class="label-<?= $securityUpdate ? 'yellow' : 'white' ?>"><?= $event['Version'] ?></span>
                        </p>
                    </div>

                    <div>
                        <?= Label::packageState($event['State']) ?>
                    </div>
                </div>
            </div>
            <?php
        endforeach ?>
    </div>
</div>
