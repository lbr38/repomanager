<!DOCTYPE html>
<html>
<?php
include_once('includes/head.inc.php');
require_once('models/Autoloader.php');
Autoloader::loadAll();
require_once('functions/common-functions.php');
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