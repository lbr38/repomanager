<?php

/**
 *  Debug mode
 */

if (DEBUG_MODE == "enabled") {
    echo '<b>Mode debug activé</b>';
    if (!empty($_POST)) {
        echo '<br>POST : <pre>';
        print_r($_POST);
        echo '</pre>';
    }
    if (!empty($_GET)) {
        echo '<br>GET : <pre>';
        print_r($_GET);
        echo '</pre>';
    }
}
?>
<header>
    <nav id="menu">
        <div id="title">
            <a href="index.php"><span>Repomanager</span></a>
        </div>

        <div class="menu-sub-container">
            <div>
                <a href="index.php">
                    <?php
                    if (__ACTUAL_URI__ == '/' or __ACTUAL_URI__ == '/index.php' or __ACTUAL_URI__ == '/explore.php' or __ACTUAL_URI__ == '/stats.php') {
                        echo '<span class="underline">';
                    } else {
                        echo '<span class="header-link">';
                    } ?>
                    <img src="ressources/icons/menu.png" class="icon" />REPOS
                    </span>
                </a>
            </div>
        </div>

        <div class="menu-sub-container">
            <?php
            if (AUTOMATISATION_ENABLED == "yes") : ?>
                <div>
                    <a href="planifications.php">
                        <?php
                        if (__ACTUAL_URI__ == '/planifications.php') {
                            echo '<span class="underline">';
                        } else {
                            echo '<span class="header-link">';
                        } ?>
                            <img src="ressources/icons/calendar.png" class="icon" />PLANIFICATIONS
                        </span>
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="menu-sub-container">
            <?php
            if (MANAGE_HOSTS == "yes") : ?>
                <div>
                    <a href="hosts.php">
                        <?php
                        if (__ACTUAL_URI__ == '/hosts.php') {
                            echo '<span class="underline">';
                        } else {
                            echo '<span class="header-link">';
                        } ?>
                            <img src="ressources/icons/server.png" class="icon" />GESTION DES HOTES
                        </span>
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="menu-sub-container">
            <?php
            if (Models\Common::isadmin() and MANAGE_PROFILES == "yes") : ?>
                <div>
                    <a href="profiles.php">
                        <?php
                        if (__ACTUAL_URI__ == '/profiles.php') {
                            echo '<span class="underline">';
                        } else {
                            echo '<span class="header-link">';
                        } ?>
                            <img src="ressources/icons/stack.png" class="icon" />GESTION DES PROFILS
                        </span>
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="menu-sub-container">
            <?php
            if (Models\Common::isadmin()) : ?>
                <div>
                    <a href="configuration.php">
                        <?php
                        if (__ACTUAL_URI__ == '/configuration.php') {
                            echo '<span class="underline">';
                        } else {
                            echo '<span class="header-link">';
                        } ?>
                           <img src="ressources/icons/settings.png" class="icon" />ADMINISTRATION
                        </span>
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="menu-sub-container">
            <div id="header-refresh-container">
                <a href="run.php">
                    <?php
                    if (__ACTUAL_URI__ == '/run.php') {
                        echo '<span class="underline">';
                    } else {
                        echo '<span class="header-link">';
                    } ?>
                    <img src="ressources/icons/rocket.png" class="icon" />OPERATIONS
                    </span>
                </a>

                <div id="header-refresh">

                    <?php
                        $op = new \Controllers\Operation();

                        /**
                         *  On récupère les opérations ou les planifications en cours si il y en a
                         */
                        $opsRunning = $op->listRunning('manual');
                        $plansRunning = $op->listRunning('plan');

                        /**
                         *  On les compte
                         */
                        $opsRunningCount = count($opsRunning);
                        $plansRunningCount = count($plansRunning);

                        /**
                         *  On les additionne
                         */
                        $totalRunningCount = $opsRunningCount + $plansRunningCount;

                        /**
                         *  Affichage d'une pastille de notification en fonction du nombre d'opérations en cours
                         */
                    if ($totalRunningCount > 0) {
                        echo '<span class="op-total-running bkg-red">' . $totalRunningCount . '</span>';
                    }

                    /**
                     *  Si il y a au moins 1 opération est en cours alors on affiche ses détails
                     */
                    if ($totalRunningCount > 0) {
                        echo '<div class="header-op-container">';

                            /**
                             *  On affiche chaque opération en cours
                             */
                        foreach ($opsRunning as $opRunning) {
                            $opId = $opRunning['Id'];
                            $opPid = $opRunning['Pid'];
                            $opLogfile = $opRunning['Logfile'];
                            if (!empty($opRunning['Action'])) {
                                $opAction = $opRunning['Action'];
                            }
                            ?>

                            <div class="header-op-subdiv btn-large-red">
                                <span>
                                    <a href="run.php?logfile=<?=$opLogfile?>">
                                    <?php
                                    if ($opAction == "new") {
                                        echo 'Nouveau repo ';
                                    }
                                    if ($opAction == "update") {
                                        echo 'Mise à jour ';
                                    }
                                    if ($opAction == "env") {
                                        echo 'Nouvel env. ';
                                    }
                                    if ($opAction == "removeEnv") {
                                        echo 'Suppression de l\'env. ';
                                    }
                                    if ($opAction == "reconstruct") {
                                        echo 'Reconstruction des metadonnées ';
                                    }
                                    if ($opAction == "duplicate") {
                                        echo 'Duplication ';
                                    }
                                    if ($opAction == "delete") {
                                        echo 'Suppression ';
                                    }

                                    /**
                                     *  Affichage du nom du repo ou du groupe en cours de traitement
                                     */
                                    $op->printRepoOrGroup($opId);
                                    ?>
                                    </a>
                                </span>
                                <span title="Stopper l'opération">
                                    <a href="run.php?stop=<?=$opPid?>">⛔</a>
                                </span>
                            </div>
                            <?php
                        }

                        /**
                         *  On affiche chaque planification en cours
                         */
                        foreach ($plansRunning as $planRunning) {
                            $opId = $planRunning['Id'];
                            $opPid = $planRunning['Pid'];
                            $opLogfile = $planRunning['Logfile'];
                            if (!empty($planRunning['Action'])) {
                                $planAction = $planRunning['Action'];
                            }
                            if (!empty($planRunning['Id_repo_source'])) {
                                $opRepoSource = $planRunning['Id_repo_source'];
                            }
                            ?>
                
                            <div class="header-op-subdiv btn-large-red">
                                <span>
                                    <a href="run.php?logfile=<?= $opLogfile ?>">
                                    <?php
                                    if ($planAction == "new") {
                                        echo 'Nouveau repo ';
                                    }
                                    if ($planAction == "update") {
                                        echo 'Mise à jour ';
                                    }
                                    if ($planAction == "env") {
                                        echo 'Nouvel env. ';
                                    }

                                    /**
                                     *  Affichage du nom du repo ou du groupe en cours de traitement
                                     */
                                    $op->printRepoOrGroup($opId);
                                    ?>
                                    </a>
                                </span>
                                <span title="Stopper l'opération">
                                    <a href="run.php?stop=<?=$opPid?>">⛔</a>
                                </span>
                            </div>
                            <?php
                        }

                        echo '</div>';

                        unset($opsRunning, $plansRunning);
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="menu-sub-container">
            <div id="userspace">
                <?php
                if (__ACTUAL_URI__ == '/user.php') {
                    echo '<span class="underline">';
                } else {
                    echo '<span class="header-link">';
                } ?>
                    <a href="user.php" title="Accéder à l'espace personnel">
                        <?php
                            echo $_SESSION['username'];
                        if (!empty($_SESSION['first_name'])) {
                            echo ' (' . $_SESSION['first_name'] . ')';
                        }
                        ?>
                    </a>
                    <a href="logout.php" title="Se déconnecter">
                        <img src="../ressources/icons/power.png" class="icon" />
                    </a>
                </span>
            </div>
        </div>
    </nav>
</header>

<?php
/**
 *  Affichage d'un bandeau constant si des erreurs ont été rencontrées lors du chargement des constantes
 */
if (!empty(__LOAD_GENERAL_ERROR > 0)) { ?>
    <section>
        <section class="missing-param-alert">
            <span class="yellowtext">Certains paramètres de configuration de l'onglet <a href="configuration.php">Configuration</a> sont vides ou invalides, ce qui pourrait engendrer un dysfonctionnement de Repomanager. Il est recommandé de terminer la configuration avant d'exécuter quelconque opération.</span>
        </section>
        <section class="missing-param-alert">
            <?php
            foreach (__LOAD_ERROR_MESSAGES as $message) {
                echo '<span class="yellowtext">' . $message . '</span><br>';
            } ?>
        </section>
    </section>
<?php }

include('maintenance.inc.php'); ?>