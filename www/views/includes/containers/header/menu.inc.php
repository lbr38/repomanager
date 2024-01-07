<header class="reloadable-container" container="header/menu">
    <nav id="menu">
        <div>
            <div id="title">
                <a href="/"><span>Repomanager</span></a>
            </div>

            <?php
            /**
             *  REPOS tab
             */
            if (__ACTUAL_URI__[1] == '' or __ACTUAL_URI__[1] == 'browse' or __ACTUAL_URI__[1] == 'stats') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div class="<?= $headerMenuClass ?>">
                <a href="/">
                    <div class="flex align-item-center column-gap-3">
                        <img src="/assets/icons/menu.svg" class="icon" />
                        <span class="menu-section-title">REPOS</span>
                    </div>
                </a>
            </div>

            <?php
            /**
             *  PLANIFICATIONS tab
             */
            if (__ACTUAL_URI__[1] == 'plans') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (PLANS_ENABLED == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/plans">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/calendar.svg" class="icon" />
                            <span class="menu-section-title">PLANIFICATIONS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

            /**
             *  MANAGE HOSTS tab
             */
            if (__ACTUAL_URI__[1] == 'hosts' or __ACTUAL_URI__[1] == 'host') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (MANAGE_HOSTS == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/hosts">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/server.svg" class="icon" />
                            <span class="menu-section-title">MANAGE HOSTS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

            /**
             *  MANAGE PROFILES tab
             */
            if (__ACTUAL_URI__[1] == 'profiles') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (IS_ADMIN and MANAGE_PROFILES == "true") : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/profiles">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/stack.svg" class="icon" />
                            <span class="menu-section-title">MANAGE PROFILES</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

            /**
             *  SETTINGS tab
             */
            if (__ACTUAL_URI__[1] == 'settings') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            }

            if (IS_ADMIN) : ?>
                <div class="<?= $headerMenuClass ?>">
                    <a href="/settings">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/settings.svg" class="icon" />
                            <span class="menu-section-title">SETTINGS</span>
                        </div>
                    </a>
                </div>
                <?php
            endif;

            /**
             *  OPERATIONS tab
             */
            if (__ACTUAL_URI__[1] == 'run') {
                $headerMenuClass = 'menu-sub-container-underline';
            } else {
                $headerMenuClass = 'menu-sub-container';
            } ?>

            <div id="header-refresh-container" class="<?= $headerMenuClass ?>">
                <div>
                    <a href="/run">
                        <div class="flex align-item-center column-gap-3">
                            <img src="/assets/icons/rocket.svg" class="icon" />
                            <span class="menu-section-title">OPERATIONS</span>
                        </div>
                    </a>

                    <div id="header-refresh">
                        <?php
                        $op = new \Controllers\Operation\Operation();
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
                            echo '<span class="op-total-running">' . $totalRunningCount . '</span>';
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
                                    <a href="/run?view-logfile=<?=$opLogfile?>">
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
                                    <span title="Stop operation" class="kill-btn" pid="<?= $opPid ?>">
                                        <img src="/assets/icons/delete.svg" class="icon">
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
                                        <a href="/run?view-logfile=<?= $opLogfile ?>">
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
                                    <span title="Stop operation" class="kill-btn" pid="<?= $opPid ?>">
                                        <img src="/assets/icons/delete.svg" class="icon">
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
            <div>
                <div class="flex column-gap-5 align-item-center">
                    <span class="round-item bkg-<?= $currentLoadColor ?>"></span>
                    <span class="lowopacity-cst font-size-11">CPU load: <?= $currentLoad ?></span>
                </div>
            </div>

            <div class="menu-sub-container relative">
                <img src="/assets/icons/alarm.svg" class="icon-lowopacity slide-panel-btn" slide-panel="general/notification" title="Show notifications" />
                <?php
                if (NOTIFICATION != 0) : ?>
                    <span id="notification-count"><?= NOTIFICATION ?></span>
                    <?php
                endif ?>
            </div>

            <?php
            /**
             *  History tab
             */
            if (IS_ADMIN) {
                echo '<a href="/history"><img src="/assets/icons/time.svg" class="icon-lowopacity" title="History" /></a>';
            } ?>

            <div class="<?= $headerMenuClass ?>">
                <div class="flex align-item-center column-gap-3 slide-panel-btn lowopacity pointer" slide-panel="general/userspace" title="Userspace">
                    <img src="/assets/icons/user.svg" class="icon" />
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