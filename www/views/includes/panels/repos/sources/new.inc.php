<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/new">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/new" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/new">
            <h3>ADD SOURCE REPOSITORY</h3>

            <form id="add-source-repo-form" autocomplete="off">
                <h6 class="required">REPOSITORY TYPE</h6>
                <div class="switch-field">
                    <?php
                    // Arch Linux source repositories are only used by mirrors, they are always available
                    $sourceTypes = array_merge(RPM_REPO == 'true' ? ['rpm'] : [], DEB_REPO == 'true' ? ['deb'] : [], ['arch']);

                    foreach ($sourceTypes as $index => $sourceType) : ?>
                        <input type="radio" id="repoType_<?= $sourceType ?>" name="addSourceRepoType" value="<?= $sourceType ?>" <?= $index == 0 ? 'checked' : '' ?> />
                        <label for="repoType_<?= $sourceType ?>"><?= $sourceType ?></label>
                        <?php
                    endforeach ?>
                </div>

                <h6 class="required">NAME</h6>
                <p class="note">Must be unique with no spaces or special characters (hyphen and underscore allowed)</p>
                <input type="text" name="addSourceName" placeholder="e.g. nginx" required />

                <h6 class="required">URL</h6>
                <p class="note">URL to the root of the repository. For arch, use the <code>Server</code> URL of a pacman mirrorlist, e.g. <code>https://geo.mirror.pkgbuild.com/$repo/os/$arch</code>. Variables are supported, please check the notes in <a href="https://docs.repomanager.net/configuration/source-repositories/#add-a-source-repository-manually" class="note-link" target="_blank" rel="noopener noreferrer">Add a source repository manually</a> for more information.</p>
                <input type="text" name="addSourceUrl" placeholder="e.g. http://nginx.org/packages/debian" required />

                <br><br>
                <button type="submit" class="btn-small-green" title="Add source repository">Add</button>
            </form>
        </div>
    </div>
</div>