<?php ob_start(); ?>

<form id="repo-edit-form" autocomplete="off" repo-id="<?= $repoController->getRepoId() ?>">
    <div class="form-block form-block-accent-<?= $repoController->getPackageType() == 'deb' ? 'red' : 'blue' ?>">
        <?php include(ROOT . '/views/includes/forms/edit.inc.php'); ?>
    </div>

    <br>
    <button type="submit" class="btn-large-red">Edit</button>
</form>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/edit';
$slidePanelTitle = 'EDIT REPOSITORY PROPERTIES';

include(ROOT . '/views/includes/slide-panel.inc.php');
