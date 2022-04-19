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

        <div>
            <?php
            if (AUTOMATISATION_ENABLED == "yes") {
                echo '<a href="planifications.php">';
                if (__ACTUAL_URI__ == '/planifications.php') {
                    echo '<span class="underline">';
                } else {
                    echo '<span class="header-link">';
                }
                echo '
                <img src="ressources/icons/calendar.png" class="icon" />PLANIFICATIONS
                </span>
                </a>';
            } ?>
        </div>

        <div>
            <?php
            if (MANAGE_HOSTS == "yes") {
                echo '<a href="hosts.php">';
                if (__ACTUAL_URI__ == '/hosts.php') {
                    echo '<span class="underline">';
                } else {
                    echo '<span class="header-link">';
                }
                echo '
                <img src="ressources/icons/server.png" class="icon" />GESTION DES HOTES
                </span>
                </a>';
            } ?>
        </div>

        <div>
            <?php
            if (Common::isadmin() and MANAGE_PROFILES == "yes") {
                echo '<a href="profiles.php">';
                if (__ACTUAL_URI__ == '/profiles.php') {
                    echo '<span class="underline">';
                } else {
                    echo '<span class="header-link">';
                }
                echo '
                <img src="ressources/icons/stack.png" class="icon" />GESTION DES PROFILS
                </span>
                </a>';
            } ?>
        </div>

        <div>
            <?php
            if (Common::isadmin()) {
                echo '<a href="configuration.php">';
                if (__ACTUAL_URI__ == '/configuration.php') {
                    echo '<span class="underline">';
                } else {
                    echo '<span class="header-link">';
                }
                echo '
                <img src="ressources/icons/settings.png" class="icon" />ADMINISTRATION
                </span>
                </a>';
            } ?>
        </div>

        <div id="header-refresh-container">
            <a href="run.php">
                <?php
                if (__ACTUAL_URI__ == '/run.php' AND __QUERY_STRING__ != 'reload') {
                    echo '<span class="underline">';
                } else {
                    echo '<span class="header-link">';
                } ?>
                <img src="ressources/icons/rocket.png" class="icon" />OPERATIONS
                </span>
            </a>

            <?php
                $op = new Operation();
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
                            $opPid = $opRunning['Pid'];
                            $opLogfile = $opRunning['Logfile'];
                            if (!empty($opRunning['Action'])) { 
                                $opAction = $opRunning['Action'];
                            }

                            /**
                             *  Si un repo source est renseigné, on récupère son nom
                             */
                            if (!empty($opRunning['Id_repo_source'])) {
                                $opRepoSource = $opRunning['Id_repo_source'];

                                /**
                                 *  Si le repo source retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
                                 */
                                if (is_numeric($opRepoSource)) {
                                    try {
                                        $stmt = $op->db->prepare("SELECT * FROM repos WHERE Id = :id");
                                        $stmt->bindValue(':id', $opRepoSource);
                                        $result = $stmt->execute();
                                    } catch (Exception $e) {
                                        Common::dbError($e);
                                    }

                                    while ($datas = $result->fetchArray()) {
                                        $name = $datas['Name'];
                                        if (OS_FAMILY == "Debian") {
                                            $dist = $datas['Dist'];
                                            $section = $datas['Section'];
                                        }
                                    }

                                /**
                                 *  Si le repo source retourné n'est pas un entier, c'est qu'il n'a pas encore été intégré en BDD et qu'il ne possède donc pas d'ID, on récupère alors directement son nom
                                 */
                                } else {
                                    $opRepoSource = explode('|', $opRepoSource);
                                    $name = $opRepoSource[0];
                                    if (OS_FAMILY == "Debian") {
                                        if (!empty($opRepoSource[1])) $dist = $opRepoSource[1];
                                        if (!empty($opRepoSource[2])) $section = $opRepoSource[2];
                                    }
                                }
                            }

                            /**
                             *  Si un repo cible est renseigné, on récupère son nom
                             */
                            if (!empty($opRunning['Id_repo_target'])) {
                                $opRepoTarget = $opRunning['Id_repo_target'];

                                /**
                                 *  Si le repo cible retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
                                 */
                                if (is_numeric($opRepoTarget)) {
                                    try {
                                        $stmt = $op->db->prepare("SELECT * FROM repos WHERE Id = :id");
                                        $stmt->bindValue(':id', $opRepoTarget);
                                        $result = $stmt->execute();
                                    } catch (Exception $e) {
                                        Common::dbError($e);
                                    }

                                    while ($datas = $result->fetchArray()) {
                                        $name = $datas['Name'];
                                        if (OS_FAMILY == "Debian") {
                                            $dist = $datas['Dist'];
                                            $section = $datas['Section'];
                                        }
                                    }

                                /**
                                 *  Si le repo cible retourné n'est pas un entier, c'est qu'il n'a pas encore été intégré en BDD et qu'il ne possède donc pas d'ID, on récupère alors directement son nom
                                 */
                                } else {
                                    $opRepoTarget = explode('|', $opRepoTarget);
                                    $name = $opRepoTarget[0];
                                    if (OS_FAMILY == "Debian") {
                                        if (!empty($opRepoTarget[1])) $dist = $opRepoTarget[1];
                                        if (!empty($opRepoTarget[2])) $section = $opRepoTarget[2];
                                    }
                                }
                            }

                            echo '<div class="header-op-subdiv btn-large-red">';
                                echo '<a href="run.php?logfile=' . $opLogfile . '">';
                                    if ($opAction == "new") {
                                        if (OS_FAMILY == "Redhat") echo 'Nouveau repo <span class="label-white">' . $name . '</span>';
                                        if (OS_FAMILY == "Debian") echo 'Nouvelle section <span class="label-white">' . $name . ' ❯ ' . $dist . ' ❯ ' . $section . '</span>';
                                    } 
                                    if ($opAction == "update") {
                                        if (OS_FAMILY == "Redhat") echo 'Mise à jour <span class="label-white">' . $name . '</span>';
                                        if (OS_FAMILY == "Debian") echo 'Mise à jour <span class="label-white">' . $name . ' ❯ ' . $dist . ' ❯ ' . $section . '</span>';
                                    }
                                    if ($opAction == "env") {
                                        if (OS_FAMILY == "Redhat") echo 'Nouvel env. <span class="label-white">' . $name . '</span>';
                                        if (OS_FAMILY == "Debian") echo 'Nouvel env. <span class="label-white">' . $name . ' ❯ ' . $dist . '❯' . $section . '</span>';
                                    }
                                    if ($opAction == "reconstruct") {
                                        if (OS_FAMILY == "Redhat") echo 'Reconstruction des metadonnées <span class="label-white">'. $name . '</span>';
                                        if (OS_FAMILY == "Debian") echo 'Reconstruction des métadonnées <span class="label-white">' . $name . ' ❯ ' . $dist . ' ❯ ' . $section . '</span>';
                                    }
                                    if ($opAction == "duplicate") {
                                        if (OS_FAMILY == "Redhat") echo 'Duplication <span class="label-white">' . $name . '</span>';
                                        if (OS_FAMILY == "Debian") echo 'Duplication <span class="label-white">' . $name . ' ❯ ' . $dist . ' ❯ ' . $section . '</span>';
                                    }
                                    if ($opAction == "delete") {
                                        echo 'Suppression <span class="label-white">' . $name . '</span>';
                                    }
                                echo '</a>';
                                echo ' | <a href="run.php?stop=' . $opPid . '">Stop</a>';
                            echo '</div>';
                        }

                        /**
                         *  On affiche chaque planification en cours
                         */
                        foreach ($plansRunning as $planRunning) {
                            $opPid = $planRunning['Pid'];
                            $opLogfile = $planRunning['Logfile'];
                            if (!empty($planRunning['Action'])) {
                                $planAction = $planRunning['Action'];
                            }
                            if (!empty($planRunning['Id_repo_source'])) {
                                $opRepoSource = $planRunning['Id_repo_source'];
                            }
        
                            /**
                             *  Si un repo source est renseigné, on récupère son nom
                             */
                            if (!empty($planRunning['Id_repo_source'])) {
                                $opRepoSource = $planRunning['Id_repo_source'];
        
                                /**
                                 *  Si le repo source retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
                                 */
                                if (is_numeric($opRepoSource)) {
                                    try {
                                        $stmt = $op->db->prepare("SELECT * FROM repos WHERE Id = :id");
                                        $stmt->bindValue(':id', $opRepoSource);
                                        $result = $stmt->execute();
                                    } catch (Exception $e) {
                                        Common::dbError($e);
                                    }
        
                                    while ($datas = $result->fetchArray()) {
                                        $name = $datas['Name'];
                                        if (OS_FAMILY == "Debian") {
                                            $dist = $datas['Dist'];
                                            $section = $datas['Section'];
                                        }
                                    }
        
                                /**
                                 *  Si le repo source retourné n'est pas un entier, c'est qu'il n'a pas encore été intégré en BDD et qu'il ne possède donc pas d'ID, on récupère alors directement son nom
                                 */
                                } else {
                                    $opRepoSource = explode('|', $opRepoSource);
                                    $name = $opRepoSource[0];
                                    if (OS_FAMILY == "Debian") {
                                        $dist = $opRepoSource[1];
                                        $section = $opRepoSource[2];
                                    }
                                }
                            }
        
                            /**
                             *  Si un repo cible est renseigné, on récupère son nom
                             */
                            if (!empty($planRunning['Id_repo_target'])) { 
                                $opRepoTarget = $planRunning['Id_repo_target'];
                                
                                /**
                                 *  Si le repo cible retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
                                 */
                                if (is_numeric($opRepoTarget)) {
                                    try {
                                        $stmt = $op->db->prepare("SELECT * FROM repos WHERE Id = :id");
                                        $stmt->bindValue(':id', $opRepoTarget);
                                        $result = $stmt->execute();
                                    } catch (Exception $e) {
                                        Common::dbError($e);
                                    }
        
                                    while ($datas = $result->fetchArray()) {
                                        $name = $datas['Name'];
                                        if (OS_FAMILY == "Debian") {
                                            $dist = $datas['Dist'];
                                            $section = $datas['Section'];
                                        }
                                    }
        
                                /**
                                 *  Si le repo cible retourné n'est pas un entier, c'est qu'il n'a pas encore été intégré en BDD et qu'il ne possède donc pas d'ID, on récupère alors directement son nom
                                 */
                                } else {
                                    $opRepoTarget = explode('|', $opRepoTarget);
                                    $name = $opRepoTarget[0];
                                    if (OS_FAMILY == "Debian") {
                                        $dist = $opRepoTarget[1];
                                        $section = $opRepoTarget[2];
                                    }
                                }
                            }
        
                            echo '<div class="header-op-subdiv btn-large-red">';
                                echo '<a href="run.php?logfile=' . $opLogfile . '">';
                                    if ($planAction == "new") {
                                        if (OS_FAMILY == "Redhat") echo 'Nouveau repo <span class="label-white">' . $name . '</span>';
                                        if (OS_FAMILY == "Debian") echo 'Nouvelle section <span class="label-white">' . $name . ' ❯ ' . $dist . ' ❯ ' . $section . '</span>';
                                    } 
                                    if ($planAction == "update") {
                                        if (OS_FAMILY == "Redhat") echo 'Mise à jour <span class="label-white">' . $name . '</span>';
                                        if (OS_FAMILY == "Debian") echo 'Mise à jour <span class="label-white">' . $name . ' ❯ ' . $dist . ' ❯ ' . $section . '</span>';
                                    }
                                    if ($planAction == "env") {
                                        if (OS_FAMILY == "Redhat") echo 'Nouvel env. <span class="label-white">' . $name . '</span>';
                                        if (OS_FAMILY == "Debian") echo 'Nouvel env. <span class="label-white">' . $name . ' ❯ ' . $dist . '❯' . $section . '</span>';
                                    }
                                    /**
                                     *  Autres actions qui pourraient être possibles dans le futur :
                                     */
                                    // if ($planAction == "reconstruct") {
                                    //     if (OS_FAMILY == "Redhat") echo 'Reconstruction des metadonnées <span class="label-white">'. $name . '</span>';
                                    //     if (OS_FAMILY == "Debian") echo 'Reconstruction des métadonnées <span class="label-white">' . $name . ' ❯ ' . $dist . ' ❯ ' . $section . '</span>';
                                    // }
                                    // if ($planAction == "duplicate") {
                                    //     if (OS_FAMILY == "Redhat") echo 'Duplication <span class="label-white">' . $name . '</span>';
                                    //     if (OS_FAMILY == "Debian") echo 'Duplication <span class="label-white">' . $name . ' ❯ ' . $dist . ' ❯ ' . $section . '</span>';
                                    // }
                                    // if ($planAction == "delete") {
                                    //     echo 'Suppression <span class="label-white">' . $name . '</span>';
                                    // }
                                echo '</a>';
                                echo ' | <a href="run.php?stop=' . $opPid . '">Stop</a>';
                            echo '</div>';
                        }
                    echo '</div>';

                    unset($opsRunning, $plansRunning);
                }
            ?>
        </div>

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
                            echo ' ('.$_SESSION['first_name'].')';
                        }
                    ?>
                </a>
                <a href="logout.php" title="Se déconnecter">
                    <img src="../ressources/icons/power.png" class="icon" />
                </a>
            </span>
        </div>
    </nav>
</header>

<?php
/**
 *  Affichage d'un bandeau constant si des erreurs ont été rencontrées lors du chargement des constantes
 */
if (!empty(__LOAD_GENERAL_ERROR > 0)) { ?>
    <section class="main">
        <section class="missing-param-alert">
            <span class="yellowtext">Certains paramètres de configuration de l'onglet <a href="configuration.php">Configuration</a> sont vides ou invalides, ce qui pourrait engendrer un dysfonctionnement de Repomanager. Il est recommandé de terminer la configuration avant d'exécuter quelconque opération.</span>
        </section>
        <section class="missing-param-alert">
            <?php
            foreach (__LOAD_ERROR_MESSAGES as $message) {
                echo '<span class="yellowtext">'.$message.'</span><br>';
            } ?>
        </section>
    </section>
<?php }

include('maintenance.inc.php'); ?>