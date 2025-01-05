<section class="section-left reloadable-container" container="tasks/log">
    <h3>LOG</h3>

    <div class="flex justify-space-between">
        <div id="log-refresh-container" task-id="<?= $taskId ?>" task-status="<?= $taskInfo['Status'] ?>" <?php echo ($legacyLog === true) ? 'legacy="true"' : '' ?>>
            <?= $output ?>
        </div>

        <div id="scroll-btns-container">
            <div id="scroll-btns">
                <?php
                // TODO : Remove this button in one year, was used to display the old log content
                if ($legacyLog) : ?>
                    <div id="display-log-btn" display="true" class="round-btn-green pointer margin-bottom-15" title="Show details">
                        <img src="/assets/icons/view-off.svg" />
                    </div>
                    <?php
                endif;

                // Print the auto scroll button only if the task is running
                if ($taskInfo['Status'] == 'running') : ?>
                    <div class="pointer margin-bottom-15">
                        <?php
                        $autoscroll = 'true';

                        if (!empty($_COOKIE['autoscroll'])) {
                            $autoscroll = $_COOKIE['autoscroll'];
                        }

                        if ($autoscroll == 'true') : ?>
                            <div id="autoscroll-btn" class="round-btn-yellow" title="Disable auto refresh and scroll">
                                <img src="/assets/icons/pause.svg" />
                            </div>
                            <?php
                        else : ?>
                            <div id="autoscroll-btn" class="round-btn-green" title="Enable auto refresh and scroll">
                                <img src="/assets/icons/play.svg" />
                            </div>
                            <?php
                        endif ?>
                    </div>
                    <?php
                endif ?>
            </div>
        </div>
    </div>
</section>