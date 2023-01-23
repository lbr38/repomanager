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
                <a href="/"><span>Repomanager</span></a>
            </div>

            <?php
            /**
             *  REPOS tab
             */
            if (__ACTUAL_URI__ == '/' or __ACTUAL_URI__ == '/browse' or __ACTUAL_URI__ == '/stats') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div class="<?= $headerMenuClass ?>">
                <a href="/">
                    <img src="resources/icons/menu.svg" class="icon" />REPOS
                </a>
            </div>

            <?php
            /**
             *  PLANIFICATIONS tab
             */
            if (__ACTUAL_URI__ == '/plans') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (PLANS_ENABLED == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="plans">
                        <img src="resources/icons/calendar.svg" class="icon" />PLANIFICATIONS
                    </a>
                </div>
                <?php
            endif ?>

            <?php
            /**
             *  MANAGE HOSTS tab
             */
            if (__ACTUAL_URI__ == '/hosts' or __ACTUAL_URI__ == '/host') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (MANAGE_HOSTS == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/hosts">
                        <img src="resources/icons/server.svg" class="icon" />MANAGE HOSTS
                    </a>
                </div>
                <?php
            endif ?>

            <?php
            /**
             *  MANAGE PROFILES tab
             */
            if (__ACTUAL_URI__ == '/profiles') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (IS_ADMIN and MANAGE_PROFILES == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/profiles">
                        <img src="resources/icons/stack.svg" class="icon" />MANAGE PROFILES
                    </a>
                </div>
                <?php
            endif ?>

            <?php
            /**
             *  SETTINGS tab
             */
            if (__ACTUAL_URI__ == '/settings') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (IS_ADMIN) : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/settings">
                        <img src="resources/icons/settings.svg" class="icon" />SETTINGS
                    </a>
                </div>
                <?php
            endif ?>

            <?php
            /**
             *  OPERATIONS tab
             */
            if (__ACTUAL_URI__ == '/run') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div id="header-refresh-container" class="<?= $headerMenuClass ?>">
                <div>
                    <a href="run">
                        <?php
                        if (__ACTUAL_URI__ == '/run') {
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
                                        <a href="/run?logfile=<?=$opLogfile?>">
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
                                        <a href="/run?stop=<?=$opPid?>">⛔</a>
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
                                        <a href="/run?logfile=<?= $opLogfile ?>">
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
                                        <a href="/run?stop=<?=$opPid?>">⛔</a>
                                    </span>
                                </div>
                                <?php
                            }

                            echo '</div>';

                            unset($opsRunning, $plansRunning);
                        } ?>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="menu-sub-container relative">
                <img id="print-notification-btn" src="resources/icons/info.svg" class="icon-lowopacity" title="Show notifications" />
                <?php
                if (NOTIFICATION != 0) : ?>
                    <span id="notification-count"><?= NOTIFICATION ?></span>
                    <?php
                endif ?>
            </div>
            <?php
            /**
             *  Userspace tab
             */
            if (__ACTUAL_URI__ == '/userspace') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div class="<?= $headerMenuClass ?>">
                <a href="/userspace" title="Userspace">
                    <img src="resources/icons/user.svg" class="icon" />

                    <?php
                    echo $_SESSION['username'];

                    if (!empty($_SESSION['first_name'])) {
                        echo ' (' . $_SESSION['first_name'] . ')';
                    } ?>
                </a>
            </div>
                
            <div class="menu-sub-container">
                <a href="/logout" title="Logout">
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
            <span class="yellowtext">Some settings from the <a href="/settings"><b>settings tab</b></a> contain missing value that could generate errors on Repomanager. Please finalize the configuration before running any operation.</span>
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
if (filesize(SERVICE_LOG)) :
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
endif;

/**
 *  Display warning if a required php module is not enabled/installed
 */



include('maintenance.inc.php');
include('update.inc.php'); ?>