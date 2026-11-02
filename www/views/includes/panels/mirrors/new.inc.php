<?php ob_start(); ?>

<form id="task-form" autocomplete="off">
    <div class="task-form-params form-block" domain="mirror" action="create">
        <h6 class="required">PACKAGE TYPE</h6>
        <div class="switch-field">
            <input type="radio" id="mirror-package-type-rpm" class="task-param" param-name="package-type" name="mirror-package-type" value="rpm" <?= RPM_REPO == 'true' ? 'checked' : '' ?> />
            <label for="mirror-package-type-rpm">rpm</label>
            <input type="radio" id="mirror-package-type-deb" class="task-param" param-name="package-type" name="mirror-package-type" value="deb" <?= RPM_REPO != 'true' ? 'checked' : '' ?> />
            <label for="mirror-package-type-deb">deb</label>
            <input type="radio" id="mirror-package-type-arch" class="task-param" param-name="package-type" name="mirror-package-type" value="arch" />
            <label for="mirror-package-type-arch">arch</label>
        </div>

        <h6 class="required">REPOSITORY TYPE</h6>
        <p class="note" mirror-field="rpm deb">Curated: packages selected from a source repository, metadata rebuilt and signed by Repomanager. Local: your own packages. Mirror: upstream repository copied as is, with its original metadata and signatures.</p>
        <p class="note" mirror-field="arch">Arch Linux repositories can only be mirrored.</p>
        <div class="switch-field" mirror-field="rpm deb">
            <!-- Curated and local repositories have their own form, selecting these types opens it (see events/mirror/actions.js) -->
            <input type="radio" id="mirror-repo-type-curated" name="mirror-repo-type" value="curated" />
            <label for="mirror-repo-type-curated">Curated</label>
            <input type="radio" id="mirror-repo-type-local" name="mirror-repo-type" value="local" />
            <label for="mirror-repo-type-local">Local</label>
            <input type="radio" id="mirror-repo-type-mirror" name="mirror-repo-type" value="mirror" checked />
            <label for="mirror-repo-type-mirror">Mirror</label>
        </div>

        <h6 class="required">NAME</h6>
        <p class="note" mirror-field="rpm">The mirror will be published under <code><?= WWW_MIRRORS_DIR_URL ?>/rpm/&lt;name&gt;/&lt;release version&gt;/&lt;environment&gt;/</code>. Several mirrors can have the same name with different release versions.</p>
        <p class="note" mirror-field="deb">The mirror will be published under <code><?= WWW_MIRRORS_DIR_URL ?>/deb/&lt;name&gt;/&lt;environment&gt;/</code></p>
        <p class="note" mirror-field="arch">The mirror will be published under <code><?= WWW_MIRRORS_DIR_URL ?>/arch/&lt;name&gt;/&lt;environment&gt;/</code>, each repository in <code>&lt;repository&gt;/os/&lt;architecture&gt;/</code> as on Arch Linux mirrors.</p>
        <input type="text" class="task-param" param-name="name" placeholder="e.g. debian-bookworm" />

        <h6 class="required">SOURCE REPOSITORY</h6>
        <p class="note">The repository to mirror from. Want more? <span class="note pointer lowopacity get-panel-btn" panel="repos/sources/list">Add or import a source repository</span>.</p>

        <?php
        foreach (['rpm' => $rpmSourcesList, 'deb' => $debSourcesList, 'arch' => $archSourcesList] as $packageType => $sourcesList) : ?>
            <div mirror-field="<?= $packageType ?>">
                <?php
                if (empty($sourcesList)) : ?>
                    <p class="note">No <?= $packageType ?> source repositories available. Please add a source repository first.</p>
                    <?php
                else : ?>
                    <select class="task-param" param-name="source" package-type="<?= $packageType ?>">
                        <option value="">Select a source repository</option>
                        <?php
                        foreach ($sourcesList as $source) {
                            $name = htmlspecialchars(json_decode($source['Definition'], true)['name'] ?? '');
                            echo '<option value="' . $name . '">' . $name . '</option>';
                        } ?>
                    </select>
                    <?php
                endif ?>
            </div>
            <?php
        endforeach ?>

        <div mirror-field="deb">
            <h6 class="required">DISTRIBUTION(S)</h6>
            <p class="note">All selected distributions are mirrored together and share the same pool, as upstream. Clients select the distribution in their <code>sources.list</code>. Not needed for flat (non-compliant) source repositories.</p>
            <select class="task-param" param-name="dist" package-type="deb" multiple></select>

            <h6 class="required">COMPONENTS</h6>
            <p class="note">Selected components are mirrored for each selected distribution.</p>
            <select class="task-param" param-name="components" package-type="deb" multiple></select>
        </div>

        <div mirror-field="rpm">
            <h6 class="required">RELEASE VERSION</h6>
            <p class="note">Replaces <code>$releasever</code> in the source repository URL.</p>
            <select class="task-param" param-name="releasever" package-type="rpm">
                <option value=""></option>
            </select>
        </div>

        <div mirror-field="arch">
            <h6 class="required">REPOSITORIES</h6>
            <p class="note">All selected repositories are mirrored together (e.g. core, extra). Replaces <code>$repo</code> in the source repository URL.</p>
            <select class="task-param" param-name="repos" package-type="arch" multiple></select>
        </div>

        <h6 class="required">ARCHITECTURE</h6>
        <p class="note" mirror-field="rpm">One repository per architecture if the source URL contains <code>$basearch</code>, otherwise the whole repository is mirrored.</p>
        <p class="note" mirror-field="deb"><code>src</code> also mirrors source packages.</p>
        <p class="note" mirror-field="arch">Replaces <code>$arch</code> in the source repository URL. <code>any</code> packages are included in each architecture repository.</p>
        <?php
        foreach (['rpm' => RPM_ARCHS, 'deb' => DEB_ARCHS] as $packageType => $archs) : ?>
            <div mirror-field="<?= $packageType ?>">
                <select class="task-param" param-name="arch" package-type="<?= $packageType ?>" multiple>
                    <?php
                    foreach ($archs as $arch) {
                        echo '<option value="' . $arch . '">' . $arch . '</option>';
                    } ?>
                </select>
            </div>
            <?php
        endforeach ?>

        <!-- Arch Linux architectures are populated with the values of the selected source -->
        <div mirror-field="arch">
            <select class="task-param" param-name="arch" package-type="arch" multiple></select>
        </div>

        <h6>CHECK GPG SIGNATURES</h6>
        <p class="note">Verify upstream metadata signature with the GPG keys imported in Repomanager. Most Arch Linux repositories do not sign their database, packages signatures are then checked by pacman on clients.</p>
        <label class="onoff-switch-label">
            <input type="checkbox" class="onoff-switch-input task-param" param-name="gpg-check" value="true" checked />
            <span class="onoff-switch-slider"></span>
        </label>

        <h6>POINT AN ENVIRONMENT</h6>
        <p class="note">Optional. Environments make the snapshot available to clients.</p>
        <select class="task-param" param-name="env" multiple>
            <?php
            foreach ($envs as $env) {
                echo '<option value="' . htmlspecialchars($env) . '">' . htmlspecialchars($env) . '</option>';
            } ?>
        </select>

        <h6>DESCRIPTION</h6>
        <input type="text" class="task-param" param-name="description" placeholder="Optional" />

        <h6>TAGS</h6>
        <p class="note">Optional. Add tags to the mirror. Tags can be used to filter repositories.</p>
        <select class="task-param" param-name="tags" multiple>
            <?php
            foreach ($tags as $tag) {
                echo '<option value="' . htmlspecialchars($tag, ENT_QUOTES) . '">' . htmlspecialchars($tag) . '</option>';
            } ?>
        </select>

        <?php
        $retention = null;
        $retentionParamClass = 'task-param';
        include(ROOT . '/views/includes/forms/retention.inc.php');

        // Possibility to add to a group, if there is at least one group
        if (!empty($groups)) : ?>
            <h6>ADD TO GROUP</h6>
            <select class="task-param" param-name="group">
                <option value="">Select a group</option>
                <?php
                foreach ($groups as $group) {
                    echo '<option value="' . $group['Id'] . '">' . htmlspecialchars($group['Name']) . '</option>';
                } ?>
            </select>
            <?php
        endif ?>
    </div>

    <?php
    $scheduleForm['action'] = 'create';
    include_once(ROOT . '/views/includes/forms/tasks/schedule.inc.php'); ?>

    <br>
    <button class="task-confirm-btn btn-large-red">Execute now</button>
</form>

<script>
$(document).ready(function(){
    // Distribution, release version and components are populated with the predefined values of the selected source, new values can be typed
    myselect2.convert('select.task-param[param-name="dist"]', 'Select or type distribution(s)', true);
    myselect2.convert('select.task-param[param-name="releasever"]', 'Select or type a release version', true);
    myselect2.convert('select.task-param[param-name="components"]', 'Select or type component(s)', true);
    myselect2.convert('select.task-param[param-name="repos"]', 'Select or type repository(ies)', true);
    myselect2.convert('select.task-param[param-name="arch"][package-type="arch"]', 'Select or type architecture(s)', true);
    myselect2.convert('select.task-param[param-name="arch"]:not([package-type="arch"])', 'Select architecture(s)');
    myselect2.convert('select.task-param[param-name="env"]', 'Select environment(s)');
    myselect2.convert('select.task-param[param-name="tags"]', 'Specify tags', true);
    myselect2.convert('select.task-param[param-name="schedule-day"]', 'Select day(s)', true);
    myselect2.convert('select.task-param[param-name="schedule-reminder"]', 'Select reminder(s)', true);
    myselect2.convert('select.task-param[param-name="schedule-recipient"]', 'Select or add recipient(s)', true);

    mirrorFormPrintFields();
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'mirrors/new';
$slidePanelTitle = 'NEW REPOSITORY';

include(ROOT . '/views/includes/slide-panel.inc.php');
