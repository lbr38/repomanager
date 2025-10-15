<?php
$stepClass = '';
$titleClass = '';

// If the step has substeps content
if (!empty($step['substeps'])) {
    $stepClass = 'show-step-content-btn pointer';
}

// If the step has an error, the title will be red
if ($step['status'] == 'error' or $step['status'] == 'stopped') {
    $titleClass = 'bkg-red';
}

// If the step is completed, the title will be green
if ($step['status'] == 'completed') {
    $titleClass = 'bkg-green';
} ?>

<div class="task-step <?= $stepClass ?>" task-id="<?= $taskId ?>" step="<?=  $stepIdentifier ?>" status="<?= $step['status'] ?>">
    <div class="task-step-title <?= $titleClass ?>">
        <!-- Step title -->
        <p><?= $step['title'] ?></p>

        <!-- Step status -->
        <div class="task-step-status">
            <div class="flex column-gap-10 align-item-center">
                <?php
                if ($step['status'] == 'running') {
                    $icon = 'loading.svg';
                    $message = 'Running';
                }

                if ($step['status'] == 'completed') {
                    $icon = 'check.svg';
                    $message = 'Completed';
                }

                if ($step['status'] == 'stopped') {
                    $icon = 'warning-red.svg';
                    $message = 'Task stopped by the user';
                }

                if ($step['status'] == 'error') {
                    $icon = 'error.svg';
                    $message = 'Error';
                }

                // If the step has no status, hide the icon, this is mostly used for the DURATION step
                if ($step['status'] == 'none') {
                    $icon = null;
                }

                // If a custom message is set, use it
                if (!empty($step['message'])) {
                    $message = $step['message'];
                }

                if ($icon != null) {
                    echo '<img src="/assets/icons/' . $icon . '" class="icon-np" />';
                }

                echo '<p>' . $message . '</p>'; ?>
            </div>
        </div>

        <!-- Step duration -->
        <div class="flex flex-direction-column row-gap-5 align-item-right justify-end">
            <?php
            echo '<p class="font-size-13">' . \Controllers\Utils\Convert::microtimeToTime($step['start']) . '</p>';

            if (!empty($step['duration'])) {
                echo '<p class="font-size-13">' . \Controllers\Utils\Convert::microtimeToHuman($step['duration']) . '</p>';
            } ?>
        </div>
    </div>
</div>
