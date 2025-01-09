<?php ob_start(); ?>

<?= $formContent; ?>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/edit';

include(ROOT . '/views/includes/slide-panel.inc.php');
