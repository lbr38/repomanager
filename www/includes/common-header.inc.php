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
<header id="here">
<nav>
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
        <li><a href="configuration.php">Paramètres</a></li>
        <?php

        $OR_status = operationRunning();
        $PR_status = planificationRunning();

        /**
         *   Cas où il n'y a aucune opération en cours (manuelle ou planifiée)
         */
        if ($OR_status === false AND $PR_status === false) {
            echo '<li><span class="li-operation-not-running"><a href="run.php">Aucune opération en cours</a></span></li>';
        }
        /**
         *  Cas où il y a une ou plusieurs opérations en cours
         */
        if ($OR_status !== false) {
            echo '<li><span class="li-operation-running"><a href="run.php">Opération en cours</a></span>';
            echo '<ul class="sub-menu">';
            /**
             *  Pour chaque opération, on récupère son PID et son fichier de LOG
             */
            foreach ($OR_status as $OR) {
                $pid = $OR['pid'];
                $pidFile = $OR['pidFile'];
                $logFile = $OR['logFile'];
                if (!empty($OR['action']))  {  $action = $OR['action']; }
                if (!empty($OR['name']))    {  $name = $OR['name']; }
                if (!empty($OR['dist']))    {  $dist = $OR['dist']; }
                if (!empty($OR['section'])) { $section = $OR['section']; }
                if ($action == "new") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Nouveau repo ($name)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Nouvelle section ($name - $dist - $section)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                } elseif ($action == "update") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Mise à jour ($name)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Mise à jour ($name - $dist - $section)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                } else {
                    echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">$logFile</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                }
            }
            echo '</ul>';
            echo '</li>';
        }
        if ($PR_status !== false) {
            echo '<li><span class="li-operation-running"><a href="run.php">Planification en cours</a></span>';
            echo '<ul class="sub-menu">';
            /**
             *  Pour chaque planification, on récupère son PID et son fichier de LOG
             */
            foreach ($PR_status as $PR) {
                $pid = $PR['pid'];
                $pidFile = $PR['pidFile'];
                $logFile = $PR['logFile'];
                if (!empty($PR['action']))  { $action = $PR['action']; }
                if (!empty($PR['name']))    { $name = $PR['name']; }
                if (!empty($PR['dist']))    { $dist = $PR['dist']; }
                if (!empty($PR['section'])) { $section = $PR['section']; }
                if ($action == "new") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Nouveau repo ($name)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Nouvelle section ($name - $dist - $section)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                } elseif ($action == "update") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Mise à jour ($name)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Mise à jour ($name - $dist - $section)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                } elseif ($action == "->") {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Bascule d'env. ($name)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">Bascule d'env. ($name - $dist - $section)</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                    }
                } else {
                    echo "<li><span class=\"li-operation-running\"><a href=\"run.php?logfile=$logFile\">$logFile</a> | <a href=\"run.php?stop=${pid}\">Stop</a></span></li>";
                }
            }
            echo '</ul>';
            echo '</li>';
        } ?>
      </ul>
    </nav>
</header>


<?php
if ($EMPTY_CONFIGURATION_VARIABLES > 0) {
echo '
<section class="main">
    <section class="center">
        <span class="yellowtext">Certains paramètres de configuration de l\'onglet <a href="configuration.php">Paramètres</a> sont vides, ce qui pourrait engendrer un dysfonctionnement de Repomanager. Il est recommandé de terminer la configuration avant d\'exécuter quelconque opération.</span>
    </section>
</section>';
} ?>

<script>
// script jQuery d'autorechargement du menu dans le header. Permet de recharger le bouton opération en cours automatiquement :
/*$(document).ready(function(){
setInterval(function(){
      $("#here").load(window.location.href + " #menu" );
}, 10000);
});*/
</script>