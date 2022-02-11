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
<nav>
    <ul class="menu">
        <li><a href="index.php"><span id="title">Repomanager</span></a></li>
        <?php
        if (__ACTUAL_URI__ == '/' OR __ACTUAL_URI__ == '/index.php' OR __ACTUAL_URI__ == '/operation.php' OR __ACTUAL_URI__ == '/explore.php') {
            echo '<li><a href="index.php"><span class="underline">Opérations</span></a></li>';
        } else {
            echo '<li><a href="index.php"><span>Opérations</span></a></li>';
        }
        if (AUTOMATISATION_ENABLED == "yes") {
            if (__ACTUAL_URI__ == '/planifications.php') {
                echo '<li><a href="planifications.php"><span class="underline">Planifications</span></a></li>';
            } else {
                echo '<li><a href="planifications.php"><span>Planifications</span></a></li>';
            }
        }
        if (MANAGE_HOSTS == "yes") {
            if (__ACTUAL_URI__ == '/hosts.php') {
                echo '<li><a href="hosts.php"><span class="underline">Gestion des hôtes</span></a></li>';
            } else {
                echo '<li><a href="hosts.php"><span>Gestion des hôtes</span></a></li>';
            }
        }
        if (Common::isadmin() AND MANAGE_PROFILES == "yes") {
            if (__ACTUAL_URI__ == '/profiles.php') {
                echo '<li><a href="profiles.php"><span class="underline">Gestion des profils</span></a></li>';
            } else {
                echo '<li><a href="profiles.php"><span>Gestion des profils</span></a></li>';
            }
        }
        /**
         *  La page d'administration s'affiche uniquement pour les utilisateurs dont le role est 'super-administrator' ou 'administrator'
         */
        if (Common::isadmin()) {
            if (__ACTUAL_URI__ == '/configuration.php') {
                echo '<li><a href="configuration.php"><span class="underline">Administration</span></a></li>';
            } else {
                echo '<li><a href="configuration.php"><span>Administration</span></a></li>';
            }
        }

        echo '<li id="header-refresh-container">';
        echo '<div class="li-op-subdiv">';
            $op = new Operation();
            $opsRunning = $op->listRunning('manual');
            $plansRunning = $op->listRunning('plan');

            /**
             *   Cas où il n'y a aucune opération en cours (manuelle ou planifiée)
             */
            if ($opsRunning === false AND $plansRunning === false) {
                echo '<a href="run.php"><span class="li-operation-not-running">Aucune opération en cours</span></a>';
            }

            /**
             *  Cas où il y a une ou plusieurs opérations en cours
             */
            if ($opsRunning !== false) {
                echo '<a href="run.php"><span class="li-operation-running">Opération en cours</span></a>';
                echo '<ul class="sub-menu">';
                /**
                 *  Pour chaque opération, on récupère son PID et son fichier de LOG
                 */
                foreach ($opsRunning as $opRunning) {
                    $opPid = $opRunning['Pid'];
                    $opLogfile = $opRunning['Logfile'];
                    if (!empty($opRunning['Action'])) { $opAction = $opRunning['Action']; }

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
                                $stmt = $op->db->prepare("SELECT * FROM repos WHERE Id=:id AND Status = 'active'");
                                $stmt->bindValue(':id', $opRepoSource);
                                $result = $stmt->execute();
                            } catch(Exception $e) {
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
                                $stmt = $op->db->prepare("SELECT * FROM repos WHERE Id=:id AND Status = 'active'");
                                $stmt->bindValue(':id', $opRepoTarget);
                                $result = $stmt->execute();
                            } catch(Exception $e) {
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

                    if ($opAction == "new") {
                        if (OS_FAMILY == "Redhat") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Nouveau repo ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                        if (OS_FAMILY == "Debian") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Nouvelle section ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    } 
                    if ($opAction == "update") {
                        if (OS_FAMILY == "Redhat") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Mise à jour ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                        if (OS_FAMILY == "Debian") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Mise à jour ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($opAction == "reconstruct") {
                        if (OS_FAMILY == "Redhat") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Reconstruction des metadonnées ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                        if (OS_FAMILY == "Debian") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Reconstruction des métadonnées ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($opAction == "duplicate") {
                        if (OS_FAMILY == "Redhat") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Duplication ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                        if (OS_FAMILY == "Debian") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Duplication ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($opAction == "delete") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Suppression ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    if ($opAction == "deleteDist") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Suppression ($name - $dist)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    if ($opAction == "deleteSection") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Suppression ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                }
                echo '</ul>';
                echo '</div>';
            }

            echo '<div class="li-plan-subdiv">';
            if ($plansRunning !== false) {
                echo '<a href="run.php"><span class="li-operation-running">Planification en cours</span></a>';
                echo '<ul class="sub-menu">';
                /**
                 *  Pour chaque planification, on récupère son PID et son fichier de LOG
                 */
                foreach ($plansRunning as $planRunning) {
                    $opPid = $planRunning['Pid'];
                    $opLogfile = $planRunning['Logfile'];
                    if (!empty($planRunning['Action'])) { $planAction = $planRunning['Action']; }
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
                                $stmt = $op->db->prepare("SELECT * FROM repos WHERE Id=:id AND Status = 'active'");
                                $stmt->bindValue(':id', $opRepoSource);
                                $result = $stmt->execute();
                            } catch(Exception $e) {
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
                                $stmt = $op->db->prepare("SELECT * FROM repos WHERE id=:id AND Status = 'active'");
                                $stmt->bindValue(':id', $opRepoTarget);
                                $result = $stmt->execute();
                            } catch(Exception $e) {
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
                    
                    if ($planAction == "new") {
                        if (OS_FAMILY == "Redhat") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Nouveau repo ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                        if (OS_FAMILY == "Debian") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Nouvelle section ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($planAction == "update") {
                        if (OS_FAMILY == "Redhat") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Mise à jour ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                        if (OS_FAMILY == "Debian") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Mise à jour ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($planAction == "changeEnv" OR strpos($planAction, '->') !== false) {
                        if (OS_FAMILY == "Redhat") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Créat. d'env. ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                        if (OS_FAMILY == "Debian") echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$opLogfile\">Créat. d'env. ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                }
                echo '</ul>';
                echo '</div>';
            }

        echo '</li>'; // Fermeture du li id="refresh-me-header-container"
        unset($opsRunning, $plansRunning); ?>

        </ul>
        <div id="userspace">
            <a href="user.php" class="lowopacity" title="Accéder à l'espace personnel">
                <?php
                    echo $_SESSION['username'];
                    if (!empty($_SESSION['first_name'])) {
                        echo ' ('.$_SESSION['first_name'].')';
                    }
                ?>
            </a>
            <a href="logout.php" class="lowopacity" title="Se déconnecter">
                <img src="../ressources/icons/power.png" class="icon" />
            </a>
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
            foreach(__LOAD_ERROR_MESSAGES as $message) {
                echo '<span class="yellowtext">'.$message.'</span><br>';
            } ?>
        </section>
    </section>
<?php } 

include('maintenance.inc.php'); ?>

<script>
// script jQuery d'autorechargement du menu dans le header. Permet de recharger le bouton opération en cours automatiquement :
$(document).ready(function(){
    setInterval(function(){
        $("#header-refresh-container").load("run.php?reload #header-refresh-container > *");
    }, 5000);
});
</script>