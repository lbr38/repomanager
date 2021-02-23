<header id="here">
  <ul id="menu">
  <span id="title"><a href="index.php">Repomanager</a></span>
  <span id="version">BETA</span>
    <li><a href="index.php">Opérations</a></li>
    <?php
    if ($AUTOMATISATION_ENABLED == "yes") {
        echo "<li><a href=\"planifications.php\">Planifications</a></li>";
    }
    if ($MANAGE_PROFILES == "yes") {
      echo "<li><a href=\"profiles.php\">Gestion des profils</a></li>";
    } ?>
    <li><a href="configuration.php">Paramètres</a></li>
    <?php 
      if (empty($OPERATION_STATUS) AND empty($PLANIFICATION_STATUS)) {
        echo "<li><a href=\"journal.php\" class=\"li-operation-not-running\">Aucune opération en cours</a></li>";
      } 
      if (!empty($OPERATION_STATUS)) {
        if ($actual_uri == "/journal.php") {
          echo '<li><a href="journal.php" class="li-operation-running">Opération en cours</a><a href="journal.php?killprocess" class="li-operation-running">Tuer le process en cours</a></li>';
        } else {
          echo '<li><a href="journal.php" class="li-operation-running">Opération en cours</a>';
        }
      }
      if (!empty($PLANIFICATION_STATUS)) {
        echo '<li><a href="viewlog.php?logfile=lastplanlog.log" class="li-operation-running">Planification en cours</a>';
      }
    ?>
  </ul>
</header>

<script>
// script jQuery d'autorechargement du menu dans le header. Permet de recharger le bouton opération en cours automatiquement :
$(document).ready(function(){
setInterval(function(){
      $("#here").load(window.location.href + " #menu" );
}, 3000);
});
</script>