<!DOCTYPE html>
<html>
<?php include('includes/head.inc.php'); ?>

<?php
/**
 *  Import des variables et fonctions nécessaires
 */
require_once('functions/load_common_variables.php');
require_once('functions/common-functions.php');
require_once('models/Host.php');
?>

<body>
<?php include('includes/header.inc.php');?>

<article>
    <section class="main">
        <section class="section-center">
            <?php include_once('includes/host.inc.php'); ?>
        </section>
    </section>
</article>

<?php include('includes/footer.inc.php'); ?>
</body>
</html>