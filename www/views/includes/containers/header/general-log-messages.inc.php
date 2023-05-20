<article class="reloadable-container" container="header/general-log-messages">
    <?php
    /**
     *  Print info or error logs if any
     */
    if (LOG > 0) : ?>
        <section class="section-main">
            <div class="div-generic-blue flex flex-direction-column row-gap-5">
                <p class="lowopacity-cst">Log messages (<?= LOG ?>)</p>
                <?php
                foreach (LOG_MESSAGES as $log) : ?>
                    <div class="flex justify-space-between">
                        <div class="flex align-item-center">
                            <?php
                            if ($log['Type'] == 'error') {
                                echo '<img src="assets/icons/redcircle.png" class="icon-small">';
                            }
                            if ($log['Type'] == 'info') {
                                echo '<img src="assets/icons/greencircle.png" class="icon-small">';
                            } ?>
                            <span><?= $log['Date'] . ' ' . $log['Time'] ?> - <?= $log['Component'] ?> - <?= $log['Message'] ?></span>
                        </div>
                        <div class="slide-btn align-self-center acquit-log-btn" log-id="<?= $log['Id'] ?>" title="Mark as read">
                            <img src="assets/icons/enabled.svg" />
                            <span>Mark as read</span>
                        </div>
                    </div>
                    <?php
                endforeach ?>
            </div>
        </section>
        <?php
    endif ?>
</article>