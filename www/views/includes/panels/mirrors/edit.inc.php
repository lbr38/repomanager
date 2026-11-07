<?php ob_start(); ?>

<form id="mirror-edit-form" autocomplete="off" mirror-id="<?= $mirror['Id'] ?>">
    <div class="form-block form-block-accent-<?= \Controllers\Mirror\Mirror::getAccent($mirror['Package_type']) ?>">
        <p class="label-white"><?= htmlspecialchars(\Controllers\Mirror\Mirror::getFullLabel($mirror)) ?></p>

        <?php
        $retention = $mirror['Retention'] ?? null;
        $retentionParamClass = 'mirror-edit-param';
        include(ROOT . '/views/includes/forms/retention.inc.php'); ?>

        <p class="note margin-top-5">At least one unused snapshot is always kept, as clients may still be reading it.</p>
    </div>

    <br>
    <button type="submit" class="btn-large-red">Edit</button>
</form>

<?php
$content = ob_get_clean();
$slidePanelName = 'mirrors/edit';
$slidePanelTitle = 'EDIT MIRROR PROPERTIES';

include(ROOT . '/views/includes/slide-panel.inc.php');
