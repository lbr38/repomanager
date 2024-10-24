<article class="reloadable-container" container="header/general-log-messages">
    <?php
    /**
     *  Print info or error logs if any
     */
    if (LOG > 0) : ?>
        <section class="section-main">
            <div class="div-generic-blue flex flex-direction-column row-gap-15">
                <p class="lowopacity-cst">Log messages (<?= LOG ?>)</p>
                <?php
                foreach (LOG_MESSAGES as $log) : ?>
                    <div class="flex justify-space-between">
                        <div class="flex flex-direction-column row-gap-5">
                            <div class="flex align-item-center column-gap-10">
                                <?php
                                if ($log['Type'] == 'error') {
                                    echo '<img src="/assets/icons/error.svg" class="icon">';
                                }
                                if ($log['Type'] == 'info') {
                                    echo '<img src="/assets/icons/check.svg" class="icon">';
                                } ?>

                                <p><?= $log['Date'] . ' ' . $log['Time'] ?> - <code><?= $log['Component'] ?></code> - <?= $log['Message'] ?></p>
                            </div>

                            <?php
                            if (!empty($log['Details'])) {
                                echo '<pre class="codeblock general-log-details hide" log-id="' . $log['Id'] . '">' . $log['Details'] . '</pre>';
                            } ?>
                        </div>

                        <div>
                            <?php
                            if (!empty($log['Details'])) : ?>
                                <div class="slide-btn-tr general-log-show-info-btn" log-id="<?= $log['Id'] ?>" title="More info">
                                    <img src="/assets/icons/info.svg" />
                                    <span>More info</span>
                                </div>
                                <?php
                            endif ?>

                            <div class="slide-btn general-log-acquit-btn" log-id="<?= $log['Id'] ?>" title="Mark as read">
                                <img src="/assets/icons/enabled.svg" />
                                <span>Mark as read</span>
                            </div>
                        </div>
                    </div>
                    <?php
                endforeach ?>
            </div>
        </section>
        <?php
    endif ?>
</article>