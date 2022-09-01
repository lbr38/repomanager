<?php

/**
 *  Debug mode
 */
if (DEBUG_MODE == "enabled") {
    echo '<b>Debug mode enabled</b>';
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
                    if (__ACTUAL_URI__ == '/' or __ACTUAL_URI__ == '/index.php' or __ACTUAL_URI__ == '/browse.php' or __ACTUAL_URI__ == '/stats.php') {
                        echo '<span class="underline">';
                    } else {
                        echo '<span class="header-link">';
                    } ?>
                    <img src="resources/icons/menu.png" class="icon" />REPOS
                    </span>
                </a>
            </div>
        </div>

        <div class="menu-sub-container">
            <?php
            if (PLANS_ENABLED == "yes") : ?>
                <div>
                    <a href="planifications.php">
                        <?php
                        if (__ACTUAL_URI__ == '/planifications.php') {
                            echo '<span class="underline">';
                        } else {
                            echo '<span class="header-link">';
                        } ?>
                            <img src="resources/icons/calendar.png" class="icon" />PLANIFICATIONS
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
                            <img src="resources/icons/server.png" class="icon" />MANAGE HOSTS
                        </span>
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="menu-sub-container">
            <?php
            if (Controllers\Common::isadmin() and MANAGE_PROFILES == "yes") : ?>
                <div>
                    <a href="profiles.php">
                        <?php
                        if (__ACTUAL_URI__ == '/profiles.php') {
                            echo '<span class="underline">';
                        } else {
                            echo '<span class="header-link">';
                        } ?>
                            <img src="resources/icons/stack.png" class="icon" />MANAGE PROFILES
                        </span>
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="menu-sub-container">
            <?php
            if (Controllers\Common::isadmin()) : ?>
                <div>
                    <a href="configuration.php">
                        <?php
                        if (__ACTUAL_URI__ == '/configuration.php') {
                            echo '<span class="underline">';
                        } else {
                            echo '<span class="header-link">';
                        } ?>
                           <img src="resources/icons/settings.png" class="icon" />SETTINGS
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
                    <img src="resources/icons/rocket.png" class="icon" />OPERATIONS
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
                            } ?>

                            <div class="header-op-subdiv btn-large-red">
                                <span>
                                    <a href="run.php?logfile=<?=$opLogfile?>">
                                        <?php
                                        if ($opAction == "new") {
                                            echo 'New repo ';
                                        }
                                        if ($opAction == "update") {
                                            echo 'Update ';
                                        }
                                        if ($opAction == "env") {
                                            echo 'New env. ';
                                        }
                                        if ($opAction == "removeEnv") {
                                            echo 'Remove env. ';
                                        }
                                        if ($opAction == "reconstruct") {
                                            echo 'Building metadata ';
                                        }
                                        if ($opAction == "duplicate") {
                                            echo 'Duplicate ';
                                        }
                                        if ($opAction == "delete") {
                                            echo 'Delete ';
                                        }

                                        /**
                                         *  Affichage du nom du repo ou du groupe en cours de traitement
                                         */
                                        $op->printRepoOrGroup($opId); ?>
                                    </a>
                                </span>
                                <span title="Stop operation">
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
                                        echo 'New repo ';
                                    }
                                    if ($planAction == "update") {
                                        echo 'Update ';
                                    }
                                    if ($planAction == "env") {
                                        echo 'New env. ';
                                    }

                                    /**
                                     *  Affichage du nom du repo ou du groupe en cours de traitement
                                     */
                                    $op->printRepoOrGroup($opId); ?>
                                    </a>
                                </span>
                                <span title="Stop operation">
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
                    <a href="user.php" title="User space">
                        <?php
                            echo $_SESSION['username'];
                        if (!empty($_SESSION['first_name'])) {
                            echo ' (' . $_SESSION['first_name'] . ')';
                        }
                        ?>
                    </a>
                    <a href="logout.php" title="Logout">
                        <img src="resources/icons/power.png" class="icon" />
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
if (__LOAD_GENERAL_ERROR > 0) : ?>
    <section>
        <section class="missing-param-alert">
            <span class="yellowtext">Some settings from the <a href="configuration.php"><b>settings tab</b></a> contain missing value that could generate errors on Repomanager. Please finalize the configuration before running any operation.</span>
        </section>
        <section class="missing-param-alert">
            <?php
            foreach (__LOAD_ERROR_MESSAGES as $message) {
                echo '<span class="yellowtext">' . $message . '</span><br>';
            } ?>
        </section>
    </section>
<?php endif;

if (!SERVICE_RUNNING) : ?>
    <section>
        <section class="missing-param-alert">
            <img src="resources/icons/warning.png" class="icon" /><span class="yellowtext">repomanager service is not running</span>
        </section>
    </section>
    <?php
endif;

/**
 *  Display repomanager service error if there is
 */
if (filesize(SERVICE_LOG)) {
    $serviceLog = file_get_contents(SERVICE_LOG);
    ?>
    <section>
        <section class="missing-param-alert">
            <img src="resources/icons/warning.png" class="icon" /><span class="yellowtext">repomanager service has error:</span>
            <br>
            <span class="yellowtext"><?= $serviceLog ?></span>
        </section>
    </section>
    <?php
}

include('maintenance.inc.php');
include('update.inc.php'); ?>