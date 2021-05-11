<header id="here">
  <ul id="menu">
  <span id="title"><a href="index.php">Repomanager</a></span>
  <span id="version">BETA</span>
  <?php 
  if ($actual_uri !== "/install.php") {
    // On n'affiche les liens du menu uniquement si on n'est pas sur la page d'install
    echo '<li><a href="index.php">Opérations</a></li>';
    if ($AUTOMATISATION_ENABLED == "yes") {
        echo '<li><a href="planifications.php">Planifications</a></li>';
    }
    if ($MANAGE_PROFILES == "yes") {
      echo '<li><a href="profiles.php">Gestion des profils</a></li>';
    }
    echo '<li><a href="configuration.php">Paramètres</a></li>';

    $operationRunning = operationRunning();
    $planificationRunning = planificationRunning();
    if (empty($operationRunning) AND empty($planificationRunning)) {
      echo '<li><a href="run.php" class="li-operation-not-running">Aucune opération en cours</a></li>';
    } 
    if (!empty($operationRunning)) {
      if ($actual_uri == "/run.php") {
        echo '<li><a href="run.php" class="li-operation-running">Opération en cours</a><a href="run.php?killprocess" class="li-operation-running">Tuer le process en cours</a></li>';
      } else {
        echo '<li><a href="run.php" class="li-operation-running">Opération en cours</a>';
      }
    }
    if (!empty($planificationRunning)) {
      echo '<li><a href="run.php" class="li-operation-running">Planification en cours</a>';
    }
  } ?>
  </ul>
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