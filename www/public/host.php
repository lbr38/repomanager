<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');
?>

<body>
<?php include_once('../includes/header.inc.php');?>

<article>
    <section class="main">
        <div class="relative">
            <?php include_once('host.inc.php'); ?>
        </div>
    </section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>
</body>
</html>