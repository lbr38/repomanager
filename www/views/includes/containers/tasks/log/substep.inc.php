<div class="task-sub-step-content" key="<?= $substepKey ?>">
    <div>
        <?php
        if ($substepStatus == 'running') {
            echo '<img src="/assets/icons/loading.svg" class="icon-np" />';
        }

        if ($substepStatus == 'completed') {
            echo '<img src="/assets/icons/check.svg" class="icon-np" />';
        }

        if ($substepStatus == 'warning') {
            echo '<img src="/assets/icons/warning.svg" class="icon-np" />';
        }

        if ($substepStatus == 'stopped') {
            echo '<img src="/assets/icons/warning-red.svg" class="icon-np" />';
        }

        if ($substepStatus == 'error') {
            echo '<img src="/assets/icons/error.svg" class="icon-np" />';
        } ?>
    </div>

    <div class="flex column-gap-10 justify-space-between">
        <div>
            <h6 class="margin-top-0"><?= $substepTitle ?></h6>

            <?php
            if (!empty($substepNote)) {
                echo '<p class="note">' . $substepNote . '</p>';
            }

            if (!empty($substepOutput)) {
                foreach ($substepOutput as $outputDetails) {
                    $time = $outputDetails['time'];
                    $type = $outputDetails['type'];
                    $message = $outputDetails['message'];

                    if ($type == 'info') {
                        echo '<p>' . $message . '</p>';
                    }

                    if ($type == 'warning') {
                        echo '<p>' . $message . '</p>';
                    }

                    if ($type == 'error') {
                        echo '<p class="redtext">' . $message . '</p>';
                    }

                    if ($type == 'pre') {
                        echo '<pre class="codeblock">' . $message . '</pre>';
                    }
                }
            }

            // If the task was stopped by the user, add an additional message
            if ($substepStatus == 'stopped') {
                echo '<p>Task stopped by the user</p>';
            } ?>
        </div>

        <div>
            <?php
            $substepDuration = null;
            $substepStartTime = null;

            // Calculate duration
            if (!empty($substepDuration)) {
                $substepDuration = \Controllers\Common::convertMicrotime($substepDuration);
            }

            // Calculate start time
            if (!empty($substepStart)) {
                $substepStartTime = \Controllers\Common::microtimeToTime($substepStart);
            }

            echo '<p class="lowopacity-cst font-size-12">';

            if (!empty($substepStartTime)) {
                echo $substepStartTime;
            }

            if (!empty($substepDuration)) {
                echo ' - took ' . $substepDuration;
            }

            echo '</p>'; ?>
        </div>
    </div>
</div>
