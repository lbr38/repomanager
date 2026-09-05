<?php ob_start(); ?>

<?= $formContent; ?>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/rename';

include(ROOT . '/views/includes/slide-panel.inc.php');
