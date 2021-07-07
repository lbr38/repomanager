<?php
/**
 *  Debug mode
 */
if ($DEBUG_MODE == "enabled") { 
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
<header id="refresh-me-container">
<nav id="refresh-me">
      <ul class="menu">
        <li><span id="title"><a href="index.php">Repomanager</a></span><span id="version">BETA</span></li>
        
        <li><a href="index.php">Opérations</a></li>
        <?php
        if ($AUTOMATISATION_ENABLED == "yes") {
            echo '<li><a href="planifications.php">Planifications</a></li>';
        }
        if ($MANAGE_PROFILES == "yes") {
            echo '<li><a href="profiles.php">Gestion des profils</a></li>';
        } ?>
        <li><a href="configuration.php">Configuration</a></li>
        <?php

        require_once("$WWW_DIR/class/Operation.php");
        $op = new Operation();
        $opsRunning = $op->listRunning('manual');
        $plansRunning = $op->listRunning('plan');

        //$planRunning = planificationRunning();

        /**
         *   Cas où il n'y a aucune opération en cours (manuelle ou planifiée)
         */
        if ($opsRunning === false AND $plansRunning === false) {
            echo '<li><span class="li-operation-not-running"><a href="run.php">Aucune opération en cours</a></span></li>';
        }
        /**
         *  Cas où il y a une ou plusieurs opérations en cours
         */
        if ($opsRunning !== false) {
            echo '<li><span class="li-operation-running"><a href="run.php">Opération en cours</a></span>';
            echo '<ul class="sub-menu">';
            /**
             *  Pour chaque opération, on récupère son PID et son fichier de LOG
             */
            foreach ($opsRunning as $opRunning) {
                $opPid = $opRunning['Pid'];
                $opLogfile = $opRunning['Logfile'];
                if (!empty($opRunning['Action'])) { $opAction = $opRunning['Action']; }
                if (!empty($opRunning['Id_repo_source'])) {
                    $opRepoSource = $opRunning['Id_repo_source'];

                    /**
                     *  A compléter (comme pour opRepoTarget)
                     */



                }
                if (!empty($opRunning['Id_repo_target'])) { 
                    $opRepoTarget = $opRunning['Id_repo_target'];
                    /**
                     *  Si le repo cible retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
                     */
                    if (is_numeric($opRepoTarget)) {
                        $stmt = $op->db->prepare("SELECT * FROM repos WHERE Id=:id AND Status=:status");
                        $stmt->bindValue(':id', $opRepoTarget);
                        $stmt->bindValue(':status', 'active');
                        $result = $stmt->execute();

                        while ($datas = $result->fetchArray()) {
                            $name = $datas['Name'];
                            if ($OS_FAMILY == "Debian") {
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
                        if ($OS_FAMILY == "Debian") {
                            $dist = $opRepoTarget[1];
                            $section = $opRepoTarget[2];
                        }
                    }
                }

                if ($opAction == "new") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Nouveau repo ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Nouvelle section ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                } 
                if ($opAction == "update") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Mise à jour ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Mise à jour ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                }
            }
            echo '</ul>';
            echo '</li>';
        }
       
        if ($plansRunning !== false) {
            echo '<li><span class="li-operation-running"><a href="run.php">Planification en cours</a></span>';
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
                if (!empty($planRunning['Id_repo_target'])) { 
                    $opRepoTarget = $planRunning['Id_repo_target'];
                    /**
                     *  Si le repo cible retourné est une chaine numérique, alors il s'agit de son ID en BDD. On va s'en servir pour récupérer les infos du repo concerné en BDD
                     */
                    if (is_numeric($opRepoTarget)) {
                        $stmt = $op->db->prepare("SELECT * FROM repos WHERE id=:id AND Status = 'active'");
                        $stmt->bindValue(':id', $opRepoTarget);
                        $result = $stmt->execute();

                        while ($datas = $result->fetchArray()) {
                            $name = $datas['Name'];
                            if ($OS_FAMILY == "Debian") {
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
                        if ($OS_FAMILY == "Debian") {
                            $dist = $opRepoTarget[1];
                            $section = $opRepoTarget[2];
                        }
                    }
                }
                
                if ($planAction == "new") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Nouveau repo ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Nouvelle section ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                }
                if ($planAction == "update") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Mise à jour ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Mise à jour ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                }
                if ($planAction == "changeEnv" OR strpos($planAction, '->') !== false) {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Créat. d'env. ($name)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?opLogfile=$opLogfile\">Créat. d'env. ($name - $dist - $section)</a> | <a href=\"run.php?stop=${opPid}\">Stop</a></span></li>";
                    }
                }
            }
            echo '</ul>';
            echo '</li>';
        } 
        
        unset($opsRunning, $plansRunning);
        ?>
      </ul>
    </nav>
</header>

<?php
/**
 *  Affichage d'un bandeau constant si des erreurs ont été rencontrées lors du chargement des constantes
 */
if ($EMPTY_CONFIGURATION_VARIABLES > 0 OR !empty($GENERAL_ERROR)) {
echo '<section class="main">';
    /**
     *  Concerne des erreurs de constantes essentielles qui sont vides :
     */
    if ($EMPTY_CONFIGURATION_VARIABLES > 0) {
        echo '<section class="center">
            <span class="yellowtext">Certains paramètres de configuration de l\'onglet <a href="configuration.php">Configuration</a> sont vides, ce qui pourrait engendrer un dysfonctionnement de Repomanager. Il est recommandé de terminer la configuration avant d\'exécuter quelconque opération.</span>
        </section>';
    }
    /**
     *  Concerne des configurations générales qui sont en erreur, ici on affiche directement le(s) message(s) d'erreur(s) placé(s) dans $GENERAL_ERROR
     */
    if (!empty($GENERAL_ERROR)) {
        echo '<section class="center">';
            foreach($GENERAL_ERROR as $message) {
                echo "<span class=\"yellowtext\">$message</span>";
            }
        echo '</section>';
    }
echo '</section>';
} ?>

<script>
// script jQuery d'autorechargement du menu dans le header. Permet de recharger le bouton opération en cours automatiquement :
/*$(document).ready(function(){
setInterval(function(){
      $("#refresh-me-container").load(window.location.href + " #refresh-me" );
}, 10000);
});*/
</script>