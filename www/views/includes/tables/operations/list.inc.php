<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        $currentPlanId = 0;

        foreach ($reloadableTableContent as $item) :
            /**
             *  If the current operation item was made in a planification, we display the planification header
             */
            if ($item['Type'] == 'plan') {
                $headerColor = 'header-light-blue margin-left-15';

                /**
                 *  Retrieve planification info
                 */
                $myplan = new \Controllers\Planification();

                $planification = $myplan->get($item['Id_plan']);

                /**
                 *  Print the planification header only if it was not already printed
                 */
                if ($currentPlanId != $item['Id_plan']) : ?>
                    <div class="table-container pointer show-logfile-btn" logfile="<?= $planification['Logfile'] ?>" title="View planification log">
                        <div>
                            <img class="icon" src="/assets/icons/calendar.svg" title="Planification" />
                        </div>

                        <div class="flex flex-direction-column row-gap-4">
                            <span>
                                <b><?= $planification['Date'] ?> <?= $planification['Time'] ?></b> Planification
                            </span>
                            <span class="lowopacity-cst">
                                <?= ucfirst($planification['Action']) ?>
                            </span>
                        </div>

                        <div></div>

                        <div class="flex align-item-center justify-end">
                            <?php
                            if ($planification['Status'] == 'done') {
                                echo '<td class="td-fit"><img class="icon-small" src="/assets/icons/greencircle.png" title="Operation done" /></td>';
                            }

                            if ($planification['Status'] == 'error') {
                                echo '<td class="td-fit"><img class="icon-small" src="/assets/icons/redcircle.png" title="Operation failed" /></td>';
                            }

                            if ($planification['Status'] == 'stopped') {
                                echo '<td class="td-fit"><img class="icon-small" src="/assets/icons/redcircle.png" title="Operation stopped by the user" /></td>';
                            } ?>
                        </div>
                    </div>
                    <?php
                endif;

                $currentPlanId = $item['Id_plan'];
            } else {
                $headerColor = '';
            } ?>

            <div class="table-container <?= $headerColor ?> pointer show-logfile-btn" logfile="<?= $item['Logfile'] ?>" title="View operation log">
                <div>
                    <?php
                    if ($item['Action'] == 'new') {
                        $icon = 'plus';
                        $actionTitle = 'New repository';
                    }

                    if ($item['Action'] == 'update') {
                        $icon = 'update';
                        $actionTitle = 'Update repository';
                    }

                    if ($item['Action'] == 'reconstruct') {
                        $icon = 'update';
                        $actionTitle = 'Rebuild metadata';
                    }

                    if ($item['Action'] == 'env') {
                        $icon = 'link';
                        $actionTitle = 'Point an environment';
                    }

                    if ($item['Action'] == 'duplicate') {
                        $icon = 'duplicate';
                        $actionTitle = 'Duplicate repository';
                    }

                    if ($item['Action'] == 'delete') {
                        $icon = 'delete';
                        $actionTitle = 'Delete repository';
                    }

                    if ($item['Action'] == 'removeEnv') {
                        $icon = 'delete';
                        $actionTitle = 'Remove environment';
                    } ?>

                    <img class="icon" src="/assets/icons/<?= $icon ?>.svg" title="<?= $actionTitle ?>" />
                </div>

                <div class="flex flex-direction-column row-gap-4">
                    <span>
                        <b><?= $item['Date'] ?> <?= $item['Time'] ?></b>
                    </span>
                    <span class="lowopacity-cst">
                        <?= $actionTitle ?>
                    </span>
                </div>
  
                <div>
                    <?= $myop->printRepoOrGroup($item['Id']); ?>
                </div>

                <div class="flex align-item-center justify-end">
                    <?php
                    /**
                     *  Print relaunch button if pool Id JSON file still exists
                     */
                    if ($item['Status'] != 'running' and file_exists(POOL . '/' . $item['Pool_id'] . '.json') and IS_ADMIN) {
                        echo '<img class="icon-lowopacity relaunch-operation-btn" src="/assets/icons/update.svg" pool-id="' . $item['Pool_id'] . '" title="Relaunch this operation with the same parameters." />';
                    }

                    if ($item['Status'] == 'running') {
                        echo '<span>running</span> <img src="/assets/images/loading.gif" class="icon" title="running" />';
                    }

                    if ($item['Status'] == 'done') {
                        echo '<img class="icon-small" src="/assets/icons/greencircle.png" title="Operation completed" />';
                    }

                    if ($item['Status'] == 'error') {
                        echo '<img class="icon-small" src="/assets/icons/redcircle.png" title="Operation has failed" />';
                    }

                    if ($item['Status'] == 'stopped') {
                        echo '<img class="icon-small" src="/assets/icons/redcircle.png" title="Operation stopped by the user" />';
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
