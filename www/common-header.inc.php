

<header id="here">
  <ul id="menu">
  <h1><a href="index.php">Repomanager</a></h1>
  <span id="version">ALPHA</span>
    <li><a href="index.php">Opérations</a></li>
    <?php
    $uri = $_SERVER['REQUEST_URI'];
    if ($AUTOMATISATION_ENABLED == "yes") {
        echo "<li><a href=\"planifications.php\">Planifications</a></li>";
    }
    if ($MANAGE_PROFILES == "yes") {
      echo "<li><a href=\"profiles.php\">Gestion des profils</a></li>";
    } ?>
    <li><a href="configuration.php">Paramètres</a></li>
    <?php if (empty($OPERATION_STATUS)) {
      echo "<li><a href=\"journal.php\" class=\"li-operation-not-running\">Aucune opération en cours</a></li>";
    } else {
      if ($uri == "/journal.php") {
        echo "<li><a href=\"journal.php\" class=\"li-operation-running\">Opération en cours</a><a href=\"journal.php?killprocess\" class=\"li-operation-running\">Tuer le process en cours</a></li>";
      } else {
        echo "<li><a href=\"journal.php\" class=\"li-operation-running\">Opération en cours</a>";
      }
    } ?>
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