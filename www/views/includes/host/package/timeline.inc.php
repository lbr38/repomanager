<div class="timeline">
    <?php
    // The first block will be displayed on the left in the timeline
    $contentPosition = 'right';

    foreach ($events as $event) :
        if ($event['State'] == "inventored") {
            $icon = 'package';
            $state = 'INVENTORED';
        }
        if ($event['State'] == "installed") {
            $icon = 'package-installed';
            $state = 'INSTALLED';
        }
        if ($event['State'] == "dep-installed") {
            $icon = 'package-installed';
            $state = 'INSTALLED (as depencency)';
        }
        if ($event['State'] == "reinstalled") {
            $icon = 'package-installed';
            $state = 'REINSTALLED';
        }
        if ($event['State'] == "upgraded") {
            $icon = 'package-updated';
            $state = 'UPDATED';
        }
        if ($event['State'] == "removed") {
            $icon = 'package-removed';
            $state = 'UNINSTALLED';
        }
        if ($event['State'] == "purged") {
            $icon = 'package-removed';
            $state = 'UNINSTALLED (purged)';
        }
        if ($event['State'] == "downgraded") {
            $icon = 'package-updated';
            $state = 'DOWNGRADED';
        } ?>

        <div class="timeline-container">
            <div class="div-generic-blue bck-blue-alt grid grid-rfr-1-2 row-gap-20 column-gap-50">
                <div class="grid grid-rfr-1-3 align-item-center column-gap-20 row-gap-20">
                    <div class="flex flex-direction-column">
                        <p><?= DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y') ?></p>
                        <p class="lowopacity-cst"><?= $event['Time'] ?></p>
                    </div>
                    
                    <div class="flex flex-direction-column row-gap-5">
                        <div class="flex align-item-center column-gap-5">
                            <img src="/assets/icons/<?=  $icon ?>.svg" class="icon-np" />
                            <h6 class="margin-top-0"><?= $state ?></h6>
                        </div>
                    </div>

                    <div class="flex flex-direction-column row-gap-5">
                        <h6 class="margin-top-0">VERSION</h6>
                        <p class="lowopacity-cst copy"><?= $event['Version'] ?></p>
                    </div>
                </div>

                <div>
                </div>
            </div>
        </div>
        <?php
    endforeach ?>
</div>
