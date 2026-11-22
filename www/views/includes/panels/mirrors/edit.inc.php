<?php ob_start(); ?>

<form id="mirror-edit-form" autocomplete="off" mirror-id="<?= $mirror['Id'] ?>">
    <div class="form-block form-block-accent-<?= \Controllers\Mirror\Mirror::getAccent($mirror['Package_type']) ?>">
        <p class="label-white"><?= htmlspecialchars(\Controllers\Mirror\Mirror::getFullLabel($mirror)) ?></p>

        <?php
        if (!empty($openbsd)) : ?>
            <h6 class="required">VERSION(S)</h6>
            <p class="note">All versions are mirrored together in the same tree: add the next OpenBSD version before running <code>sysupgrade</code> on clients, remove a version that is no longer used. Snapshots are not supported.</p>
            <select class="mirror-edit-param" param-name="versions" multiple>
                <?php
                foreach ($openbsd['versions']['available'] as $version) {
                    echo '<option value="' . htmlspecialchars($version) . '"' . (in_array($version, $openbsd['versions']['selected']) ? ' selected' : '') . '>' . htmlspecialchars($version) . '</option>';
                } ?>
            </select>

            <h6 class="required">CONTENT</h6>
            <p class="note">Mirrored for each version and architecture. A client tool can only use this mirror if its content is mirrored.</p>
            <select class="mirror-edit-param" param-name="content" multiple>
                <?php
                $contentLabels = [
                    'packages' => 'Packages and their updates (pkg_add)',
                    'syspatch' => 'Binary patches (syspatch)',
                    'sets'     => 'Install sets (sysupgrade, network installs)',
                    'sources'  => 'Sources'
                ];

                foreach (\Controllers\Mirror\Sync\Openbsd::CONTENTS as $contentName) {
                    echo '<option value="' . $contentName . '"' . (in_array($contentName, $openbsd['content']) ? ' selected' : '') . '>' . $contentLabels[$contentName] . '</option>';
                } ?>
            </select>

            <h6 class="required">ARCHITECTURE</h6>
            <p class="note">Platforms, as named by OpenBSD (e.g. amd64, arm64).</p>
            <select class="mirror-edit-param" param-name="arch" multiple>
                <?php
                foreach ($openbsd['archs']['available'] as $arch) {
                    echo '<option value="' . htmlspecialchars($arch) . '"' . (in_array($arch, $openbsd['archs']['selected']) ? ' selected' : '') . '>' . htmlspecialchars($arch) . '</option>';
                } ?>
            </select>

            <p class="note margin-top-5">Used by the next syncs: added content is retrieved, removed content is not part of the new snapshots. Existing snapshots are not modified.</p>
            <?php
        endif ?>

        <?php
        $retention = $mirror['Retention'] ?? null;
        $retentionParamClass = 'mirror-edit-param';
        include(ROOT . '/views/includes/forms/retention.inc.php'); ?>

        <p class="note margin-top-5">At least one unused snapshot is always kept, as clients may still be reading it.</p>
    </div>

    <br>
    <button type="submit" class="btn-large-red">Edit</button>
</form>

<script>
$(document).ready(function(){
    myselect2.convert('#mirror-edit-form select.mirror-edit-param[param-name="versions"]', 'Select or type version(s)', true);
    myselect2.convert('#mirror-edit-form select.mirror-edit-param[param-name="content"]', 'Select content(s)');
    myselect2.convert('#mirror-edit-form select.mirror-edit-param[param-name="arch"]', 'Select or type architecture(s)', true);
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'mirrors/edit';
$slidePanelTitle = 'EDIT MIRROR PROPERTIES';

include(ROOT . '/views/includes/slide-panel.inc.php');
