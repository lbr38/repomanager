<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/new">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/new" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/new">
            <h3>ADD SOURCE REPOSITORY</h3>

            <form id="add-source-repo-form" autocomplete="off">
                <h6 class="required">REPOSITORY TYPE</h6>
                <?php
                if (RPM_REPO == 'true' and DEB_REPO == 'true') : ?>
                    <div class="switch-field">
                        <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                        <label for="repoType_rpm">rpm</label>
                        <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" />
                        <label for="repoType_deb">deb</label>
                    </div>
                    <?php
                elseif (RPM_REPO == 'true') : ?>
                    <div class="single-switch-field">
                        <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                        <label for="repoType_rpm">rpm</label>
                    </div>
                    <?php
                elseif (DEB_REPO == 'true') : ?>
                    <div class="single-switch-field">
                        <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" checked />
                        <label for="repoType_deb">deb</label>
                    </div>
                    <?php
                endif ?>

                <h6 class="required">NAME</h6>
                <input type="text" name="addSourceName" placeholder="e.g. nginx" required />

                <h6 class="required">URL</h6>
                <input type="text" name="addSourceUrl" placeholder="https://..." required />

                <h6>GPG KEY URL</h6>
                <input type="text" name="gpgKeyURL" placeholder="https://...">

                <h6>IMPORT A GPG KEY</h6>
                <textarea id="gpgKeyText" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>
                <p class="note">Plain text format.</p>

                <br><br>
                <button type="submit" class="btn-small-green" title="Add source repository">Add</button>
            </form>
        </div>
    </div>
</div>