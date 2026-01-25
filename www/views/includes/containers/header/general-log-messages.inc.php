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
                    <div>
                        <div class="flex justify-space-between column-gap-50">
                            <div class="flex flex-direction-column row-gap-5">
                                <div class="flex align-item-center column-gap-10">
                                    <?php
                                    if ($log['Type'] == 'error') {
                                        echo '<img src="/assets/icons/error.svg" class="icon">';
                                    }
                                    if ($log['Type'] == 'info') {
                                        echo '<img src="/assets/icons/check.svg" class="icon">';
                                    } ?>

                                    <p class="wordbreakall"><?= $log['Date'] . ' ' . $log['Time'] ?> - <code><?= $log['Component'] ?></code> - <?= $log['Message'] ?></p>
                                </div>

                                
                            </div>

                            <div class="flex column-gap-10 row-gap-10">
                                <?php
                                if (!empty($log['Details'])) : ?>
                                    <div class="flex align-item-center column-gap-5 mediumopacity general-log-show-info-btn" log-id="<?= $log['Id'] ?>" title="More info">
                                        <img src="/assets/icons/info.svg" class="icon" />
                                        <p class="pointer">More info</p>
                                    </div>
                                    <?php
                                endif ?>

                                <div class="flex align-item-center column-gap-5 mediumopacity general-log-acquit-btn" log-id="<?= $log['Id'] ?>" title="Mark as read">
                                    <img src="/assets/icons/enabled.svg" class="icon" />
                                    <p class="pointer">Mark as read</p>
                                </div>
                            </div>
                        </div>

                        <?php
                        if (!empty($log['Details'])) {
                            echo '<pre class="codeblock general-log-details margin-top-10 hide" log-id="' . $log['Id'] . '">' . $log['Details'] . '</pre>';
                        } ?>
                    </div>
                    <?php
                endforeach ?>
            </div>
        </section>
        <?php
    endif ?>
</article>