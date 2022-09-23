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
} ?>

<header>
    <nav id="menu">
        <div>
            <div id="title">
                <a href="index.php"><span>Repomanager</span></a>
            </div>

            <?php
            /**
             *  REPOS tab
             */
            if (__ACTUAL_URI__ == '/' or __ACTUAL_URI__ == '/index.php' or __ACTUAL_URI__ == '/browse.php' or __ACTUAL_URI__ == '/stats.php') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div class="<?= $headerMenuClass ?>">
                <a href="index.php">
                    <img src="resources/icons/menu.svg" class="icon" />REPOS
                </a>
            </div>

            <?php
            /**
             *  PLANIFICATIONS tab
             */
            if (__ACTUAL_URI__ == '/planifications.php') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (PLANS_ENABLED == "yes") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="planifications.php">
                        <img src="resources/icons/calendar.svg" class="icon" />PLANIFICATIONS
                    </a>
                </div>
                <?php
            endif ?>

            <?php
            /**
             *  MANAGE HOSTS tab
             */
            if (__ACTUAL_URI__ == '/hosts.php' or __ACTUAL_URI__ == '/host.php') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (MANAGE_HOSTS == "yes") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="hosts.php">
                        <img src="resources/icons/server.svg" class="icon" />MANAGE HOSTS
                    </a>
                </div>
                <?php
            endif ?>

            <?php
            /**
             *  MANAGE PROFILES tab
             */
            if (__ACTUAL_URI__ == '/profiles.php') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (Controllers\Common::isadmin() and MANAGE_PROFILES == "yes") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="profiles.php">
                        <img src="resources/icons/stack.svg" class="icon" />MANAGE PROFILES
                    </a>
                </div>
                <?php
            endif ?>

            <?php
            /**
             *  SETTINGS tab
             */
            if (__ACTUAL_URI__ == '/configuration.php') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (Controllers\Common::isadmin()) : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="configuration.php">
                        <img src="resources/icons/settings.svg" class="icon" />SETTINGS
                    </a>
                </div>
                <?php
            endif ?>

            <?php
            /**
             *  OPERATIONS tab
             */
            if (__ACTUAL_URI__ == '/run.php') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div id="header-refresh-container" class="<?= $headerMenuClass ?>">
                <div>
                    <a href="run.php">
                        <?php
                        if (__ACTUAL_URI__ == '/run.php') {
                            echo '<span class="underline">';
                        } else {
                            echo '<span class="header-link">';
                        } ?>
                        <img src="resources/icons/rocket.svg" class="icon" />OPERATIONS
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
        </div>

        <div>
            <?php
            /**
             *  USERSPACE tab
             */
            if (__ACTUAL_URI__ == '/user.php') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <?php
            if (Controllers\Common::isadmin()) : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="user.php" title="Userspace">
                        <img src="resources/icons/user.svg" class="icon" />

                        <?= $_SESSION['username']; ?>

                        <?php
                        if (!empty($_SESSION['first_name'])) {
                            echo ' (' . $_SESSION['first_name'] . ')';
                        } ?>
                    </a>
                </div>
                <?php
            endif ?>

            <div class="menu-sub-container">
                <a href="logout.php" title="Logout">
                    <img src="resources/icons/power.svg" class="icon" /> Logout
                </a>
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