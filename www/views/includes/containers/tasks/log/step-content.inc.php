<?php
if (!empty($step['substeps'])) : ?>
    <div class="task-step-content hide" task-id="<?= $taskId ?>" step="<?=  $stepIdentifier ?>">
        <div class="task-sub-step-container">
            <?php
            $i = 0;
            $substeps = $step['substeps'];

            // If autoscroll is not set, set it to false
            if (!isset($autoscroll)) {
                $autoscroll = false;
            }

            // If autoscroll is enabled, display the 30 latest substeps instead of the first ones
            if ($autoscroll) {
                $substeps = array_slice($step['substeps'], -30, 30, true);
            }

            foreach ($substeps as $substepKey => $substep) :
                if ($i == 30) {
                    break;
                }

                $i++;
                $substepTitle    = $substep['title'];
                $substepStatus   = $substep['status'];
                $substepNote     = $substep['note'];
                $substepDuration = $substep['duration'];
                $substepStart    = $substep['start'];
                $substepOutput   = $substep['output'];

                // Include substep template
                include(ROOT . '/views/includes/containers/tasks/log/substep.inc.php');
            endforeach ?>
        </div>

        <div>
            <div class="step-content-btns">
                <div class="round-btn-green step-top-btn" title="Go to the top" task-id="<?= $taskId ?>" step="<?=  $stepIdentifier ?>">
                    <img src="/assets/icons/top.svg" class="icon" />
                </div>

                <div class="round-btn-green step-up-btn" title="Go up" task-id="<?= $taskId ?>" step="<?=  $stepIdentifier ?>">
                    <img src="/assets/icons/up.svg" class="icon" />
                </div>

                <div class="round-btn-green step-down-btn" title="Go down" task-id="<?= $taskId ?>" step="<?=  $stepIdentifier ?>">
                    <img src="/assets/icons/down.svg" class="icon" />
                </div>

                <div class="round-btn-green step-bottom-btn" title="Go to the bottom" task-id="<?= $taskId ?>" step="<?=  $stepIdentifier ?>">
                    <img src="/assets/icons/bottom.svg" class="icon" />
                </div>
            </div>
        </div>
    </div>
    <?php
endif ?>
