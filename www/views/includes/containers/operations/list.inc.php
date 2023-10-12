<section class="section-right reloadable-container" container="operations/list">
    <h3>HISTORY</h3>
        <?php
        /**
         *  Print running operations
         */
        if (!empty($totalRunning)) :
            echo '<h5>Running</h5>';

            foreach ($totalRunning as $itemRunning) :
                /**
                 *  If the item has a Reminder key then it's a planification
                 */
                if (array_key_exists('Reminder', $itemRunning)) {
                    /**
                     *  1. Get all informations about this planification
                     */
                    $planId = $itemRunning['Id'];
                    $planType = $itemRunning['Type'];

                    if (!empty($itemRunning['Frequency'])) {
                        $planFrequency = $itemRunning['Frequency'];
                    }

                    if (!empty($itemRunning['Date'])) {
                        $planDate = DateTime::createFromFormat('Y-m-d', $itemRunning['Date'])->format('d-m-Y');
                    }

                    if (!empty($itemRunning['Time'])) {
                        $planTime = $itemRunning['Time'];
                    }

                    $planAction = $itemRunning['Action'];
                    $planStatus = $itemRunning['Status'];
                    $planLogfile = $itemRunning['Logfile'];

                    /**
                     *  2. Then get all operations that have been launched by this planification
                     */
                    $planOpsRunning = $myop->getOperationsByPlanId($planId, 'running');

                    /**
                     *  3. Display the planification header
                     */ ?>
                    <div class="header-container">
                        <div class="header-blue">
                            <table>
                                <tr>
                                    <td class="td-fit">
                                        <img class="icon" src="/assets/icons/calendar.svg" title="Planification" />
                                    </td>
            
                                    <?php
                                    if ($planType == "plan") :
                                        if (!empty($planLogfile)) : ?>
                                            <td class="td-small pointer show-logfile-btn" logfile="<?= $planLogfile ?>">Plan <b><?= $planDate ?> <?= $planTime ?></b></td>
                                            <?php
                                        else : ?>
                                            <td>Plan <b><?= $planDate ?> <?= $planTime ?></b></td>
                                            <?php
                                        endif;
                                    endif;

                                    if ($planType == "regular") {
                                        echo "<td>Regular plan</b></td>";
                                    } ?>

                                    <td class="td-fit">
                                        running<img class="icon" src="/assets/images/loading.gif" title="Running" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php
                    /**
                     *  If there are running operations for this planification then we display them
                     */
                    if (!empty($planOpsRunning)) {
                        foreach ($planOpsRunning as $planOpRunning) {
                            $myop->printOperation($planOpRunning['Id'], true);
                        }
                    }

                    /**
                     *  If there are done operations for this planification then we display them
                     */
                    if (!empty($planOpsDone)) {
                        foreach ($planOpsDone as $planOpDone) {
                            $myop->printOperation($planOpDone['Id'], true);
                        }
                    }

                    /**
                     *  If the item doesn't have a Reminder key then it's an operation
                     */
                } else {
                    $myop->printOperation($itemRunning['Id']);
                }
                unset($planOpsRunning, $planOpsDone);
            endforeach;
        endif;

        /**
         *  Print done operations
         */
        if (!empty($totalDone) or !empty($opsFromRegularPlanDone)) :
            if (!empty($totalDone)) :
                echo '<h5>Done</h5>';

                /**
                 *  Maximal number of operations we want to display by default, the rest is hidden and can be displayed by a "Show all" button
                 *  When $i reaches the maximal number $printMaxItems, we start to hide operations
                 */
                $i = 0;
                $printMaxItems = 5;

                foreach ($totalDone as $itemDone) :
                    /**
                     *  If we have reached the maximal number of operations we want to display by default, then the following ones are hidden in a hidden container
                     *  Except if the cookie printAllOp = yes, in this case we display everything
                     */
                    if ($i > $printMaxItems) {
                        if (!empty($_COOKIE['printAllOp']) and $_COOKIE['printAllOp'] == "yes") {
                            echo '<div class="hidden-op">';
                        } else {
                            echo '<div class="hidden-op hide">';
                        }
                    }

                    /**
                     *  If the item has a Reminder key then it's a planification
                     */
                    if (array_key_exists('Reminder', $itemDone)) {
                        /**
                         *  1. Get all informations about this planification
                         */
                        $planId = $itemDone['Id'];
                        $planType = $itemDone['Type'];

                        if (!empty($itemDone['Frequency'])) {
                            $planFrequency = $itemDone['Frequency'];
                        }

                        if (!empty($itemDone['Date'])) {
                            $planDate = DateTime::createFromFormat('Y-m-d', $itemDone['Date'])->format('d-m-Y');
                        }

                        if (!empty($itemDone['Time'])) {
                            $planTime = $itemDone['Time'];
                        }

                        $planAction = $itemDone['Action'];
                        $planStatus = $itemDone['Status'];
                        $planLogfile = $itemDone['Logfile'];

                        /**
                         *  2. Then get all operations that have been launched by this planification
                         */
                        $planOpsDone = $myop->getOperationsByPlanId($planId, 'done');
                        $planOpError = $myop->getOperationsByPlanId($planId, 'error');
                        $planOpStopped = $myop->getOperationsByPlanId($planId, 'stopped');
                        $planOpsDone = array_merge($planOpsDone, $planOpError, $planOpStopped);

                        /**
                         *  3. Display the planification header
                         */ ?>
                        <div class="header-container">
                            <div class="header-blue">
                                <table>
                                    <tr>
                                        <td class="td-fit">
                                            <img class="icon" src="/assets/icons/calendar.svg" title="Planification" />
                                        </td>

                                        <?php
                                        if ($planType == "plan") {
                                            if (!empty($planLogfile)) : ?>
                                                <td class="td-small pointer show-logfile-btn" logfile="<?= $planLogfile ?>">Plan <b><?= $planDate ?> <?= $planTime ?></b></td>
                                                <?php
                                            else : ?>
                                                <td>Plan <b><?= $planDate ?> <?= $planTime ?></b></td>
                                                <?php
                                            endif;

                                            if ($planStatus == "done") {
                                                echo '<td class="td-fit"><img class="icon-small" src="/assets/icons/greencircle.png" title="Operation done" /></td>';
                                            }
                                            if ($planStatus == "error") {
                                                echo '<td class="td-fit"><img class="icon-small" src="/assets/icons/redcircle.png" title="Operation failed" /></td>';
                                            }
                                            if ($planStatus == "stopped") {
                                                echo '<td class="td-fit"><img class="icon-small" src="/assets/icons/redcircle.png" title="Operation stopped by the user" /></td>';
                                            }
                                        } ?>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    
                        <?php
                        /**
                         *  If there are done operations for this planification then we display them
                         */
                        if (!empty($planOpsDone)) {
                            foreach ($planOpsDone as $planOpDone) {
                                $myop->printOperation($planOpDone['Id'], true);
                            }
                        }
                    /**
                     *  If the item doesn't have a Reminder key then it's an operation
                     */
                    } else {
                        $myop->printOperation($itemDone['Id']);
                    }

                    unset($planOpsDone);

                    if ($i > $printMaxItems) {
                        echo '</div>'; // close <div class="hidden-op hide">
                    }

                    ++$i;
                endforeach;

                if ($i > $printMaxItems) {
                    /**
                     *  Print the 'Show all' button only if the cookie printAllOp is not set or is not equal to "yes"
                     */
                    if (!isset($_COOKIE['printAllOp']) or (!empty($_COOKIE['printAllOp']) and $_COOKIE['printAllOp'] != "yes")) {
                        echo '<p id="print-all-op" class="pointer center"><b>Show all</b> <img src="/assets/icons/down.svg" class="icon" /></p>';
                    }
                }
            endif;

            /**
             *  Print completed regular tasks
             */
            if (!empty($opsFromRegularPlanDone)) :
                echo '<h5>Completed regular tasks</h5>';

                /**
                 *  Maximal number of operations we want to display by default, the rest is hidden and can be displayed by a "Show all" button
                 *  When $i reach the maximal number $printMaxItems, we start to hide operations
                 */
                $i = 0;
                $printMaxItems = 5;

                foreach ($opsFromRegularPlanDone as $itemDone) {
                    /**
                     *  If we have reached the maximal number of operations we want to display by default, then the following operations are hidden in a hidden container
                     *  Except if the cookie printAllRegularOp = yes, in this case we display everything
                     */
                    if ($i > $printMaxItems) {
                        if (!empty($_COOKIE['printAllRegularOp']) and $_COOKIE['printAllRegularOp'] == "yes") {
                            echo '<div class="hidden-regular-op">';
                        } else {
                            echo '<div class="hidden-regular-op hide">';
                        }
                    }

                    $myop->printOperation($itemDone['Id']);
                    if ($i > $printMaxItems) {
                        echo '</div>';
                    }

                    ++$i;
                }

                if ($i > $printMaxItems) {
                    /**
                     *  On affiche le bouton Afficher tout uniquement si le cookie printAllRegularOp n'est pas en place ou n'est pas égal à "yes"
                     */
                    if (!isset($_COOKIE['printAllRegularOp']) or (!empty($_COOKIE['printAllRegularOp']) and $_COOKIE['printAllRegularOp'] != "yes")) {
                        echo '<p id="print-all-regular-op" class="pointer center"><b>Show all</b> <img src="/assets/icons/down.svg" class="icon" /></p>';
                    }
                }
            endif;
        endif ?>
</section>