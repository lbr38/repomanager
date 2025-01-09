<div class="timeline">
    <?php
    // The first block will be displayed on the left in the timeline
    $contentPosition = 'left';

    foreach ($events as $event) :
        if ($event['State'] == "inventored") {
            $contentIcon = 'package';
            $contentText = 'INVENTORED';
        }
        if ($event['State'] == "installed") {
            $contentIcon = 'check';
            $contentText = 'INSTALLED';
        }
        if ($event['State'] == "dep-installed") {
            $contentIcon = 'check';
            $contentText = 'INSTALLED (as depencency)';
        }
        if ($event['State'] == "reinstalled") {
            $contentIcon = 'check';
            $contentText = 'REINSTALLED';
        }
        if ($event['State'] == "upgraded") {
            $contentIcon = 'update-yellow';
            $contentText = 'UPDATED';
        }
        if ($event['State'] == "removed") {
            $contentIcon = 'error';
            $contentText = 'UNINSTALLED';
        }
        if ($event['State'] == "purged") {
            $contentIcon = 'error';
            $contentText = 'UNINSTALLED (purged)';
        }
        if ($event['State'] == "downgraded") {
            $contentIcon = 'rollback';
            $contentText = 'DOWNGRADED';
        } ?>

        <div class="timeline-container timeline-container-<?= $contentPosition ?>">
            <div class="table-container flex align-item-center column-gap-20">
                <img src="/assets/icons/<?=  $contentIcon ?>.svg" class="icon" />

                <div class="flex flex-direction-column">
                    <p><?= DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y') ?></p>
                    <p class="lowopacity-cst"><?= $event['Time'] ?></p>
                </div>
            
                <div class="flex flex-direction-column">
                    <h6 class="margin-top-0"><?= $contentText ?></h6>                    
                    <p class="lowopacity-cst copy"><?= $event['Version'] ?></p>
                </div>
            </div>

            <?php
            // If the previous block was on the left, we display the next one on the right and vice versa
            if ($contentPosition == "left") {
                $contentPosition = 'right';
            } elseif ($contentPosition == "right") {
                $contentPosition = 'left';
            } ?>
        </div>
        <?php
    endforeach ?>
</div>
