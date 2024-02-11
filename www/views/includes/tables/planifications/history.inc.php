<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <h3>HISTORY</h3>

        <?php
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container pointer plan-details-btn" plan-id="<?= $item['Id'] ?>" title="Show planification details">
                <div>
                    <?php
                    if ($item['Action'] == 'update') {
                        echo '<img class="icon" src="/assets/icons/update.svg" title="Action: ' . $item['Action'] . '" />';
                    } ?>
                </div>

                <div>
                    <span>
                        <b><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?> <?= $item['Time'] ?></b>
                    </span>
                </div>

                <div>
                    <?php
                    /**
                     *  Print the repo or the group
                     */
                    if (!empty($item['Id_group'])) {
                        if ($mygroup->existsId($item['Id_group']) === false) {
                            echo '<span>Unknown group (deleted)</span>';
                        } else {
                            echo '<span class="label-white">' . $mygroup->getNameById($item['Id_group']) . '</span> <span>group</span>';
                        }
                    }

                    if (!empty($item['Id_snap'])) {
                        /**
                         *  Getting all infos about the repo
                         */
                        $myrepo->getAllById('', $item['Id_snap'], '');

                        /**
                         *  Format
                         */
                        if (!empty($myrepo->getDist()) and !empty($myrepo->getSection())) {
                            echo '<span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>';
                        } else {
                            echo '<span class="label-white">' . $myrepo->getName() . '</span>';
                        }
                    } ?>
                </div>

                <div class="flex justify-end">
                    <?php
                    if ($item['Status'] == 'done') {
                        echo '<img class="icon-small" src="/assets/icons/greencircle.png" title="Task completed" />';
                    }

                    if ($item['Status'] == 'error') {
                        echo '<img class="icon-small" src="/assets/icons/redcircle.png" title="Task has failed" />';
                    }

                    if ($item['Status'] == 'stopped') {
                        echo '<img class="icon-small" src="/assets/icons/redcircle.png" title="Task stopped by the user" />';
                    } ?>
                </div>
            </div>

            <div class="hide plan-info-div margin-bottom-5" plan-id="<?= $item['Id'] ?>">
                <?php
                /**
                 *  Si la planification est en erreur alors on affiche le message d'erreur
                 */
                if ($item['Status'] == "error" and (!empty($item['Error']))) {
                    echo '<p>' . $item['Error'] . '<br><br></p>';
                } ?>

                <div>
                    <span>Action</span>
                    <span><?= $item['Action'] ?></span>
                </div>
                
                <?php
                if ($item['Action'] == 'update') : ?>
                    <div>
                        <span>Check GPG signatures</span>
                        <?php
                        if ($item['Gpgcheck'] == 'yes') {
                            echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                        } else {
                            echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                        } ?>
                    </div>

                    <div>
                        <span>Sign with GPG</span>
                        <?php
                        if ($item['Gpgresign'] == 'yes') {
                            echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                        } else {
                            echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                        } ?>
                    </div>

                    <div>
                        <span>Only sync the difference</span>
                        <?php
                        if ($item['OnlySyncDifference'] == "yes") {
                            echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                        } else {
                            echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                        } ?>
                    </div>
                    <?php
                endif ?>

                <div>
                    <span>Reminders</span>
                    <span>
                        <?php
                        if ($item['Reminder'] == 'None') {
                            echo 'None';
                        } else {
                            $item['Reminder'] = explode(',', $item['Reminder']);

                            foreach ($item['Reminder'] as $reminder) {
                                echo "$reminder days before<br>";
                            }
                        } ?>
                    </span>
                </div>
                
                <div>
                    <span>Notification on error</span>
                    <?php
                    if ($item['Notification_error'] == 'yes') {
                        echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                    } else {
                        echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                    } ?>
                </div>
                
                <div>
                    <span>Notification on success</span>
                    <?php
                    if ($item['Notification_success'] == 'yes') {
                        echo '<span><img src="/assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                    } else {
                        echo '<span><img src="/assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                    } ?>
                </div>

                <?php
                if (!empty($item['Mail_recipient'])) : ?>
                    <div>
                        <span>Contact</span>
                        <span>
                            <?php
                            $item['Mail_recipient'] = explode(',', $item['Mail_recipient']);
                            foreach ($item['Mail_recipient'] as $recipient) {
                                if (!empty($recipient)) {
                                    echo $recipient . '<br>';
                                }
                            } ?>
                        </span>
                    </div>
                    <?php
                endif; ?>
                
                <div>
                    <?php
                    if (!empty($item['Logfile'])) {
                        echo '<span>Log</span>';
                        echo '<span><a href="/run?view-logfile=' . $item['Logfile'] . '"><button class="btn-small-green"><b>Visualize</b></button></a></></span>';
                    } ?>
                </div>
            </div>

            <?php
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
