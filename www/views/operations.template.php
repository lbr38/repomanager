<section class="mainSectionLeft">
    <h3>LOG</h3>

    <div id="log-container">
        <div id="scrollButtons-container">
            <div id="scrollButtons">
                <?php
                /**
                 *  Si on a activé l'affichage de tous les logs alors on fait apparaitre tous les div cachés
                 */
                if (!empty($_COOKIE['displayFullLogs']) and $_COOKIE['displayFullLogs'] == "yes") { ?>
                    <div id="displayFullLogs-no" class="button-top-down-details pointer" title="Hide details.">
                        <img src="resources/icons/search.svg" />
                    </div> 
                    <style>
                        .getPackagesDiv { display: block; }
                        .signRepoDiv { display: block; }
                        .createRepoDiv { display: block; }
                    </style>
                    <?php
                } else {
                    echo '<div id="displayFullLogs-yes" class="button-top-down-details pointer" title="Show details."><img src="resources/icons/search.svg" /></div>';
                } ?>
                <br><br>
                <div>
                    <a href="#top" class="button-top-down" title="Go to the top."><img src="resources/icons/up.svg" /></a>
                </div>
                <div>
                    <a href="#bottom" class="button-top-down" title="Go to the bottom."><img src="resources/icons/down.svg" /></a>
                </div>
            </div>
        </div>

        <div id="log-refresh-container">
            <div id="log">
                <?php
                if ($logfile == 'none') {
                    $logfiles = array_diff(scandir(MAIN_LOGS_DIR, SCANDIR_SORT_DESCENDING), array('..', '.', 'lastlog.log'));
                    if (!empty($logfiles[1])) {
                        $logfile = $logfiles[1];
                    }
                }
                /**
                 *  Récupération du contenu du fichier de log
                 */
                if (!empty($logfile)) {
                    $output = file_get_contents(MAIN_LOGS_DIR . '/' . $logfile);
                } else {
                    $output = '';
                }
                /**
                 *  Suppression des codes ANSI (couleurs) dans le fichier
                 */
                $output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "", $output);
                echo $output;
                ?>
            </div>
        </div>
    </div>
</section>
<section class="mainSectionRight">
    <h3>HISTORY</h3>
        <?php
        /**
         *  Instanciation d'objets Planification et Operation pour pouvoir récupérer l'historique
         */
        $myplan = new \Controllers\Planification();
        $myop = new \Controllers\Operation();
        /**
         *  Récupère toutes les planifications en cours d'exécution
         */
        $plansRunning = $myplan->listRunning();
        /**
         *  Récupère toutes les opérations en cours d'exécution et qui n'ont pas été lancées par une planification (type = manual)
         */
        $opsRunning = $myop->listRunning('manual');
        /**
         *  Si les requêtes précédentes ont toutes les deux retourné un résultat, alors on merge ces résultats dans $totalRunning
         */
        if (!empty($plansRunning) and !empty($opsRunning)) {
            $totalRunning = array_merge($plansRunning, $opsRunning);
            array_multisort(array_column($totalRunning, 'Date'), SORT_DESC, array_column($totalRunning, 'Time'), SORT_DESC, $totalRunning); // On tri par date pour avoir le + récent en haut
        } elseif (!empty($plansRunning)) {
            $totalRunning = $plansRunning;
        } elseif (!empty($opsRunning)) {
            $totalRunning = $opsRunning;
        }
        /**
         *  Recupère toutes les planifications terminées
         */
        $plansDone = $myplan->listDone();
        /**
         *  Récupère toutes les opérations terminées et qui n'ont pas été lancées par une planification (type = manual)
         */
        $opsDone = $myop->listDone('manual');
        /**
         *  Récupère toutes les opérations terminées qui ont été lancées par une planification récurrente
         */
        $opsFromRegularPlanDone = $myop->listDone('plan', 'regular');
        /**
         *  Si les requêtes précédentes ont toutes les deux retourné un résultat, alors on merge ces résultats dans $totalRunning
         */
        if (!empty($plansDone) and !empty($opsDone)) {
            $totalDone = array_merge($plansDone, $opsDone);
            array_multisort(array_column($totalDone, 'Date'), SORT_DESC, array_column($totalDone, 'Time'), SORT_DESC, $totalDone); // On tri par date pour avoir le + récent en haut
        } else if (!empty($plansDone)) {
            $totalDone = $plansDone;
        } else if (!empty($opsDone)) {
            $totalDone = $opsDone;
        }
        /**
         *  Affichage des données en cours d'exécution
         */
        if (!empty($totalRunning)) :
            echo '<h5>Running</h5>';
            foreach ($totalRunning as $itemRunning) {
                /**
                 *  Si l'item possède une clé Reminder alors il s'agit d'une planification
                 */
                if (array_key_exists('Reminder', $itemRunning)) {
                    /**
                     *  1. Récupération de toutes des informations concernant cette planification
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
                     *  2. Puis récupération des opérations qui ont été exécutées par cette planification
                     */
                    $planOpsRunning = $myop->getOperationsByPlanId($planId, 'running');
                    /**
                     *  3. Affichage de l'en-tête de la planification
                     */ ?>
                            <div class="header-container">
                                <div class="header-blue">
                                    <table>
                                        <tr>
                                            <td class="td-fit">
                                                <img class="icon" src="resources/icons/calendar.svg" title="Planification" />
                                            </td>
                                            <?php
                                            /**
                                             *  On affiche un lien vers le fichier de log de la planification si il y en a un
                                             */
                                            if ($planType == "plan") {
                                                if (!empty($planLogfile)) {
                                                    echo '<td><a href="/run?logfile=' . $planLogfile . '">Plan of the <b>' . $planDate . '</b> at <b>' . $planTime . '</b></a></td>';
                                                } else {
                                                    echo '<td>Plan of the <b>' . $planDate . '</b> at <b>' . $planTime . '</b></td>';
                                                }
                                            }
                                            if ($planType == "regular") {
                                                echo "<td>Regular plan</b></td>";
                                            } ?>
                                            <td class="td-fit">
                                                running<img class="icon" src="resources/images/loading.gif" title="Running" />
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <?php
                            /**
                             *  Si il y a des opérations en cours pour cette planification alors on l'affiche
                             */
                            if (!empty($planOpsRunning)) {
                                foreach ($planOpsRunning as $planOpRunning) {
                                    $myop->printOperation($planOpRunning['Id'], true);
                                }
                            }
                            /**
                             *  Si il y a des opérations terminées pour cette planification alors on l'affiche
                             */
                            if (!empty($planOpsDone)) {
                                foreach ($planOpsDone as $planOpDone) {
                                    $myop->printOperation($planOpDone['Id'], true);
                                }
                            }
                        /**
                         *  Si l'item ne possède pas de clé Reminder alors il s'agit d'une opération
                         */
                } else {
                    $myop->printOperation($itemRunning['Id']);
                }
                unset($planOpsRunning, $planOpsDone);
            }
        endif;
        /**
         *  Affichage des données terminées
         */
        if (!empty($totalDone) or !empty($opsFromRegularPlanDone)) {
            /**
             *  Affichage des tâches terminées
             */
            if (!empty($totalDone)) {
                echo '<h5>Done</h5>';
                /**
                 *  Nombre maximal d'opérations qu'on souhaite afficher par défaut, le reste est masqué et affichable par un bouton "Afficher tout"
                 *  Lorsque $i a atteint le nombre maximal $printMaxItems, on commence à masquer les opérations
                 */
                $i = 0;
                $printMaxItems = 2;
                /**
                 *  Traitement de toutes les opérations terminées
                 */
                foreach ($totalDone as $itemDone) {
                    /**
                     *  Si on a dépassé le nombre maximal d'opération qu'on souhaite afficher par défaut, alors les suivantes sont masquées dans un container caché
                     *  Sauf si le cookie printAllOp = yes, dans ce cas on affiche tout
                     */
                    if ($i > $printMaxItems) {
                        if (!empty($_COOKIE['printAllOp']) and $_COOKIE['printAllOp'] == "yes") {
                            echo '<div class="hidden-op">';
                        } else {
                            echo '<div class="hidden-op hide">';
                        }
                    }
                    /**
                     *  Si l'item possède une clé Reminder alors il s'agit d'une planification
                     */
                    if (array_key_exists('Reminder', $itemDone)) {
                        /**
                         *  1. Récupération de toutes des informations concernant cette planification
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
                         *  2. Puis récupération des opérations qui ont été exécutées par cette planification
                         */
                        $planOpsDone = $myop->getOperationsByPlanId($planId, 'done');
                        $planOpError = $myop->getOperationsByPlanId($planId, 'error');
                        $planOpStopped = $myop->getOperationsByPlanId($planId, 'stopped');
                        $planOpsDone = array_merge($planOpsDone, $planOpError, $planOpStopped);
                        /**
                         *  3. Affichage de l'en-tête de la planification
                         */ ?>
                        <div class="header-container">
                            <div class="header-blue">
                                <table>
                                    <tr>
                                        <td class="td-fit">
                                            <img class="icon" src="resources/icons/calendar.svg" title="Planification" />
                                        </td>
                                        <?php
                                        if ($planType == "plan") {
                                            if (!empty($planLogfile)) { // On affiche un lien vers le fichier de log de la planification si il y en a un
                                                echo '<td><a href="/run?logfile=' . $planLogfile . '">Plan of the <b>' . $planDate . '</b> at <b>' . $planTime . '</b></a></td>';
                                            } else {
                                                echo "<td>Plan of the <b>$planDate</b> at <b>$planTime</b></td>";
                                            }
                                            if ($planStatus == "done") {
                                                echo '<td class="td-fit"><img class="icon-small" src="resources/icons/greencircle.png" title="Operation done" /></td>';
                                            }
                                            if ($planStatus == "error") {
                                                echo '<td class="td-fit"><img class="icon-small" src="resources/icons/redcircle.png" title="Operation failed" /></td>';
                                            }
                                            if ($planStatus == "stopped") {
                                                echo '<td class="td-fit"><img class="icon-small" src="resources/icons/redcircle.png" title="Operation stopped by the user" /></td>';
                                            }
                                        } ?>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <?php
                        /**
                         *  Si il y a des opérations terminées pour cette planification alors on l'affiche
                         */
                        if (!empty($planOpsDone)) {
                            foreach ($planOpsDone as $planOpDone) {
                                $myop->printOperation($planOpDone['Id'], true);
                            }
                        }
                    /**
                     *  Si l'item ne possède pas de clé Reminder alors il s'agit d'une opération
                     */
                    } else {
                        $myop->printOperation($itemDone['Id']);
                    }
                    unset($planOpsDone);
                    if ($i > $printMaxItems) {
                        echo '</div>'; // clôture de <div class="hidden-op hide">
                    }
                    ++$i;
                }
                if ($i > $printMaxItems) {
                    /**
                     *  On affiche le bouton Afficher uniquement si le cookie printAllOp n'est pas en place ou n'est pas égal à "yes"
                     */
                    if (!isset($_COOKIE['printAllOp']) or (!empty($_COOKIE['printAllOp']) and $_COOKIE['printAllOp'] != "yes")) {
                        echo '<p id="print-all-op" class="pointer center"><b>Show all</b> <img src="resources/icons/down.svg" class="icon" /></p>';
                    }
                }
            }
            /**
             *  Affichage des tâches récurrentes terminées
             */
            if (!empty($opsFromRegularPlanDone)) {
                echo '<h5>Completed regular tasks</h5>';
                /**
                 *  Nombre maximal d'opérations qu'on souhaite afficher par défaut, le reste est masqué et affichable par un bouton "Afficher tout"
                 *  Lorsque $i a atteint le nombre maximal $printMaxItems, on commence à masquer les opérations
                 */
                $i = 0;
                $printMaxItems = 2;
                foreach ($opsFromRegularPlanDone as $itemDone) {
                    /**
                     *  Si on a dépassé le nombre maximal d'opération qu'on souhaite afficher par défaut, alors les suivantes sont masquées dans un container caché
                     *  Sauf si le cookie printAllRegularOp = yes, dans ce cas on affiche tout
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
                        echo '<p id="print-all-regular-op" class="pointer center"><b>Show all</b> <img src="resources/icons/down.svg" class="icon" /></p>';
                    }
                }
            }
        } ?>
</section>