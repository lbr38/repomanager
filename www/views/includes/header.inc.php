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
                    <div class="flex align-item-center column-gap-3">
                        <img src="assets/icons/menu.svg" class="icon" />
                        <span class="menu-section-title">REPOS</span>
                    </div>
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
                        <div class="flex align-item-center column-gap-3">
                            <img src="assets/icons/calendar.svg" class="icon" />
                            <span class="menu-section-title">PLANIFICATIONS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

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
                        <div class="flex align-item-center column-gap-3">
                            <img src="assets/icons/server.svg" class="icon" />
                            <span class="menu-section-title">MANAGE HOSTS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

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
                        <div class="flex align-item-center column-gap-3">
                            <img src="assets/icons/stack.svg" class="icon" />
                            <span class="menu-section-title">MANAGE PROFILES</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

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
                        <div class="flex align-item-center column-gap-3">
                            <img src="assets/icons/settings.svg" class="icon" />
                            <span class="menu-section-title">SETTINGS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

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
                        <div class="flex align-item-center column-gap-3">
                            <img src="assets/icons/rocket.svg" class="icon" />
                            <span class="menu-section-title">OPERATIONS</span>
                        </div>
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
                        if ($totalRunningCount > 0) :
                            echo '<div class="header-op-container">';

                            /**
                             *  On affiche chaque opération en cours
                             */
                            foreach ($opsRunning as $opRunning) :
                                $opId = $opRunning['Id'];
                                $opPid = $opRunning['Pid'];
                                $opLogfile = $opRunning['Logfile'];
                                if (!empty($opRunning['Action'])) {
                                    $opAction = $opRunning['Action'];
                                } ?>

                                <div class="header-op-subdiv btn-large-red">
                                    <a href="/run?logfile=<?=$opLogfile?>">
                                        <span>
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
                                            } ?>
                                        </span>
                                        <?php
                                        /**
                                         *  Affichage du nom du repo ou du groupe en cours de traitement
                                         */
                                        $op->printRepoOrGroup($opId); ?>
                                    </a>
                                    <span title="Stop operation">
                                        <a href="/run?stop=<?=$opPid?>"><img src="assets/icons/delete.svg" class="icon"></a>
                                    </span>
                                </div>
                                <?php
                            endforeach;

                            /**
                             *  On affiche chaque planification en cours
                             */
                            foreach ($plansRunning as $planRunning) :
                                $opId = $planRunning['Id'];
                                $opPid = $planRunning['Pid'];
                                $opLogfile = $planRunning['Logfile'];
                                if (!empty($planRunning['Action'])) {
                                    $planAction = $planRunning['Action'];
                                }
                                if (!empty($planRunning['Id_repo_source'])) {
                                    $opRepoSource = $planRunning['Id_repo_source'];
                                } ?>
                    
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
                                        <a href="/run?stop=<?=$opPid?>"><img src="assets/icons/delete.svg" class="icon"></a>
                                    </span>
                                </div>
                                <?php
                            endforeach;

                            echo '</div>';

                            unset($opsRunning, $plansRunning);
                        endif ?>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="menu-sub-container relative">
                <img src="assets/icons/info.svg" class="icon-lowopacity slide-panel-btn" slide-panel="notification" title="Show notifications" />
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
                <div class="flex align-item-center column-gap-3 slide-panel-btn lowopacity pointer" slide-panel="userspace" title="Userspace">
                    <img src="assets/icons/user.svg" class="icon" />
                    <span class="menu-section-title">
                        <?php
                        echo $_SESSION['username'];

                        if (!empty($_SESSION['first_name']) and !empty($_SESSION['last_name'])) {
                            echo ' (' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] . ')';
                        } elseif (!empty($_SESSION['first_name'])) {
                            echo ' (' . $_SESSION['first_name'] . ')';
                        } ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>
</header>

<?php
/**
 *  Print missing parameters alert if any
 */
if (__LOAD_GENERAL_ERROR > 0) : ?>
    <section>
        <section class="missing-param-alert">
            <span class="yellowtext">Some settings from the <a href="/settings"><b>settings tab</b></a> contain missing or bad value that could generate errors on Repomanager. Please finalize the configuration before running any operation.</span>
            <br><br>
            <?php
            foreach (__LOAD_ERROR_MESSAGES as $message) {
                echo '<span>' . $message . '</span><br>';
            } ?>
        </section>
    </section>
    <?php
endif ?>

<article id="general-log-container">
    <?php
    /**
     *  Print info or error logs if any
     */
    if (LOG > 0) : ?>
        <section class="section-main">
            <div class="div-generic-blue flex flex-direction-column row-gap-5">
                <p class="lowopacity-cst">Log messages (<?= LOG ?>)</p>
                <?php
                foreach (LOG_MESSAGES as $log) : ?>
                    <div class="flex justify-space-between">
                        <div class="flex align-item-center">
                            <?php
                            if ($log['Type'] == 'error') {
                                echo '<img src="assets/icons/redcircle.png" class="icon-small">';
                            }
                            if ($log['Type'] == 'info') {
                                echo '<img src="assets/icons/greencircle.png" class="icon-small">';
                            } ?>
                            <span><?= $log['Date'] . ' ' . $log['Time'] ?> - <?= $log['Component'] ?> - <?= $log['Message'] ?></span>
                        </div>
                        <div class="slide-btn align-self-center acquit-log-btn" log-id="<?= $log['Id'] ?>" title="Mark as read">
                            <img src="assets/icons/enabled.svg" />
                            <span>Mark as read</span>
                        </div>
                    </div>
                    <?php
                endforeach ?>
            </div>
        </section>
        <?php
    endif ?>
</article>

<?php
if (!SERVICE_RUNNING) : ?>
    <section>
        <section class="missing-param-alert">
            <img src="assets/icons/warning.png" class="icon" /><span class="yellowtext">Repomanager service is not running. <?php echo (DOCKER == "true") ? 'Please restart the container.' : ''; ?></span>
        </section>
    </section>
    <?php
endif;

/**
 *  Display repomanager service error if there is
 */
if (file_exists(SERVICE_LOG)) :
    if (filesize(SERVICE_LOG)) :
        $serviceLog = file_get_contents(SERVICE_LOG); ?>
        <section>
            <section class="missing-param-alert">
                <img src="assets/icons/warning.png" class="icon" /><span class="yellowtext">Repomanager service has error:</span>
                <br><br>
                <span class="yellowtext"><?= $serviceLog ?></span>
            </section>
        </section>
        <?php
    endif;
endif;

include('maintenance.inc.php');
include('update.inc.php'); ?>