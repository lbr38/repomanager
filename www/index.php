<html>
<?php include('common-head.inc.php'); ?>

<?php
  // Import des variables nécessaires, ne pas changer l'ordre des require
  require 'common-vars.php';
  require 'common-functions.php';
  require 'common.php';
  require 'display.php';
  if ($debugMode == "enabled") { print_r($_POST); }
?>

<body>
<?php include('common-header.inc.php'); ?>

<article class="main">

  <!-- REPOS ACTIFS -->
  <article class="left">
      <?php include('common-repos-list.inc.php'); ?>
  </article>


  <!-- LISTE DES OPERATIONS -->
  <article class="right">
      <?php include('common-operations.inc.php'); ?>
  </article>

</article>

<article class="main">

    <!-- REPOS ARCHIVÉS-->
    <article class="left">
        <?php include('common-repos-archive-list.inc.php'); ?>
    </article>

    <!-- LISTE DES OPERATIONS POUR LES REPOS ARCHIVÉS -->
    <article class="right">
        <?php include('common-operations-archive.inc.php'); ?>
    </article>

</article>

<!-- divs cachées de base -->
<!-- div des groupes de repos -->
<?php include('common-groupslist.inc.php'); ?>

<!-- div des hotes et fichers de repos -->
<?php include('common-repos-sources.inc.php'); ?>

<?php include('common-footer.inc.php'); ?>
</body>
</html>