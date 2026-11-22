<?php ob_start(); ?>

<form id="task-form" autocomplete="off">
    <div class="task-form-params form-block" domain="mirror" action="create">
        <h6 class="required">PACKAGE TYPE</h6>
        <div class="switch-field">
            <?php
            // Only package types enabled in settings are available
            $packageTypes = \Controllers\Task\Form\Param\PackageType::enabled();

            foreach ($packageTypes as $index => $packageType) : ?>
                <input type="radio" id="mirror-package-type-<?= $packageType ?>" class="task-param" param-name="package-type" name="mirror-package-type" value="<?= $packageType ?>" <?= $index == 0 ? 'checked' : '' ?> />
                <label for="mirror-package-type-<?= $packageType ?>"><?= $packageType ?></label>
                <?php
            endforeach ?>
        </div>

        <div class="flex align-item-center column-gap-5 margin-top-20">
            <h6 class="required margin-top-0">REPOSITORY TYPE</h6>
            <a href="https://docs.repomanager.net/latest/usage/repository-types/" target="_blank" rel="noopener noreferrer" title="Curated, local and mirror repositories: see documentation">
                <img src="/assets/icons/question.svg" class="icon-small icon-lowopacity" />
            </a>
        </div>
        <div class="switch-field" mirror-field="arch alpine openbsd">
            <input type="radio" id="mirror-repo-type-mirror-arch" name="mirror-repo-type-arch" value="mirror" checked />
            <label for="mirror-repo-type-mirror-arch">Mirror</label>
        </div>
        <p class="note margin-top-10" mirror-field="arch">Arch Linux repositories can only be mirrored.</p>
        <p class="note margin-top-10" mirror-field="alpine">Alpine Linux repositories can only be mirrored.</p>
        <p class="note margin-top-10" mirror-field="openbsd">OpenBSD repositories can only be mirrored.</p>
        <div class="switch-field" mirror-field="rpm deb">
            <?php
            // Curated and local repositories have their own form, selecting these types opens it (see events/mirror/actions.js)
            if (\Controllers\User\Permission\Repo::allowedAction('create')) : ?>
                <input type="radio" id="mirror-repo-type-curated" name="mirror-repo-type" value="curated" />
                <label for="mirror-repo-type-curated">Curated</label>
                <input type="radio" id="mirror-repo-type-local" name="mirror-repo-type" value="local" />
                <label for="mirror-repo-type-local">Local</label>
                <?php
            endif ?>
            <input type="radio" id="mirror-repo-type-mirror" name="mirror-repo-type" value="mirror" checked />
            <label for="mirror-repo-type-mirror">Mirror</label>
        </div>
        <p class="note margin-top-10">Exact copy of a source repository, with its upstream metadata and signature.</p>

        <h6 class="required">SOURCE REPOSITORY</h6>
        <p class="note">The repository to mirror from. Want more? <span class="note pointer lowopacity get-panel-btn" panel="repos/sources/list">Add or import a source repository</span>.</p>

        <?php
        foreach (['rpm' => $rpmSourcesList, 'deb' => $debSourcesList, 'arch' => $archSourcesList, 'alpine' => $alpineSourcesList, 'openbsd' => $openbsdSourcesList] as $packageType => $sourcesList) : ?>
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

        <h6>MIRROR NAME</h6>
        <p class="note">Optional. Default will be the source repository name.</p>
        <input type="text" class="task-param" param-name="name" />

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

        <div mirror-field="alpine">
            <h6>BRANCH</h6>
            <p class="note">One mirror per branch (e.g. v3.24, edge). Replaces <code>$branch</code> in the source repository URL, required if the URL contains it.</p>
            <select class="task-param" param-name="branch" package-type="alpine">
                <option value=""></option>
            </select>

            <h6>REPOSITORIES</h6>
            <p class="note">All selected repositories are mirrored together (e.g. main, community). Replaces <code>$repo</code> in the source repository URL, required if the URL contains it. Without repository, the source URL is mirrored as a single repository.</p>
            <select class="task-param" param-name="repos" package-type="alpine" multiple></select>
        </div>

        <div mirror-field="openbsd">
            <h6 class="required">VERSION(S)</h6>
            <p class="note">All selected versions are mirrored together in the same tree, as on the official mirrors: clients set the mirror URL in <code>/etc/installurl</code> and <code>sysupgrade</code> finds the next version in it. Snapshots are not supported.</p>
            <select class="task-param" param-name="versions" package-type="openbsd" multiple></select>

            <h6 class="required">CONTENT</h6>
            <p class="note">Mirrored for each selected version and architecture, as on the official mirrors. A client tool can only use this mirror if its content is mirrored.</p>
            <select class="task-param" param-name="content" package-type="openbsd" multiple>
                <option value="packages" selected>Packages and their updates (pkg_add)</option>
                <option value="syspatch" selected>Binary patches (syspatch)</option>
                <option value="sets" selected>Install sets (sysupgrade, network installs)</option>
                <option value="sources" selected>Sources</option>
            </select>
        </div>

        <h6 class="required">ARCHITECTURE</h6>
        <p class="note" mirror-field="rpm">One repository per architecture if the source URL contains <code>$basearch</code>, otherwise the whole repository is mirrored.</p>
        <p class="note" mirror-field="deb"><code>src</code> also mirrors source packages.</p>
        <p class="note" mirror-field="arch">Replaces <code>$arch</code> in the source repository URL. <code>any</code> packages are included in each architecture repository.</p>
        <p class="note" mirror-field="alpine">Appended to the source repository URL, as apk does.</p>
        <p class="note" mirror-field="openbsd">Platforms, as named by OpenBSD (e.g. amd64, arm64). Packages are retrieved from the directory of their architecture (e.g. aarch64 for arm64).</p>
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

        <!-- Arch Linux, Alpine Linux and OpenBSD architectures are populated with the values of the selected source -->
        <?php
        foreach (['arch', 'alpine', 'openbsd'] as $packageType) : ?>
            <div mirror-field="<?= $packageType ?>">
                <select class="task-param" param-name="arch" package-type="<?= $packageType ?>" multiple></select>
            </div>
            <?php
        endforeach ?>

        <h6 mirror-field="rpm deb arch">CHECK GPG SIGNATURES</h6>
        <p class="note" mirror-field="rpm deb arch">Verify upstream metadata signature with the GPG keys imported in Repomanager. Most Arch Linux repositories do not sign their database, packages signatures are then checked by pacman on clients.</p>
        <h6 mirror-field="alpine openbsd">CHECK SIGNATURES</h6>
        <p class="note" mirror-field="alpine">Verify the indexes signature with the RSA keys of the source repository. Packages are verified against the index by apk on clients.</p>
        <p class="note" mirror-field="openbsd">Verify the files lists signature (<code>SHA256.sig</code>) with the signify keys of the source repository. Each file is verified against these lists, packages, patches and sets are verified again by pkg_add, syspatch and sysupgrade on clients.</p>
        <label class="onoff-switch-label">
            <input type="checkbox" class="onoff-switch-input task-param" param-name="gpg-check" value="true" checked />
            <span class="onoff-switch-slider"></span>
        </label>

        <h6>POINT AN ENVIRONMENT</h6>
        <p class="note">Optional. Environments make the snapshot available to clients.</p>
        <select class="task-param" param-name="env" multiple>
            <?php
            // Protected environments cannot be pointed, they are not listed
            foreach (array_column(\Controllers\Environment::getPointable(), 'Name') as $env) {
                echo '<option value="' . htmlspecialchars($env) . '"' . ($env == DEFAULT_ENV ? ' selected' : '') . '>' . htmlspecialchars($env) . '</option>';
            } ?>
        </select>
        <?= \Controllers\Environment::protectedNote() ?>

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
    myselect2.convert('select.task-param[param-name="branch"]', 'Select or type a branch', true);
    myselect2.convert('select.task-param[param-name="repos"]', 'Select or type repository(ies)', true);
    myselect2.convert('select.task-param[param-name="versions"]', 'Select or type version(s)', true);
    myselect2.convert('select.task-param[param-name="content"]', 'Select content(s)');
    myselect2.convert('select.task-param[param-name="arch"][package-type="arch"], select.task-param[param-name="arch"][package-type="alpine"], select.task-param[param-name="arch"][package-type="openbsd"]', 'Select or type architecture(s)', true);
    myselect2.convert('select.task-param[param-name="arch"][package-type="rpm"], select.task-param[param-name="arch"][package-type="deb"]', 'Select architecture(s)');
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
