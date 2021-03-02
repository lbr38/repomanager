<!DOCTYPE html>
<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Création du fichier de conf si n'existe pas (c'est pour ça qu'on est là)
  if (!file_exists("/etc/repomanager/repomanager.conf")) {
    touch("/etc/repomanager/repomanager.conf");
  }

  // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
  require 'vars/common.vars';
  require 'common-functions.php';
  if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }
?>

<body>
<?php include('common-header.inc.php'); ?>

<!-- section 'conteneur' principal englobant toutes les sections de gauche -->
<section class="main">
    <section class="center">
        <form action="install.php" method="post" autocomplete="off">



        </form>
    </section>
</section>

</body>
</html>