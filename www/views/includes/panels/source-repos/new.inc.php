<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="source-repos/new">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="source-repos/new" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="source-repos/new">
            <h3>NEW SOURCE REPOSITORY</h3>

            <form id="addSourceForm" autocomplete="off">
                <p class="form-param-title">REPO TYPE</p>
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

                <p class="form-param-title">NAME</p>
                <input type="text" name="addSourceName" required />

                <p class="form-param-title">URL</p>
                <input type="text" name="addSourceUrl" required />

                <p class="form-param-title">GPG KEY URL</p>
                <input type="text" name="gpgKeyURL" placeholder="https://...">
                <p class="form-param-note">Optional.</p>

                <p class="form-param-title">IMPORT A GPG KEY</p>
                <textarea id="gpgKeyText" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>
                <p class="form-param-note">Optional. Plain text format.</p>

                <br><br>
                <button type="submit" class="btn-small-green" title="Add source repository">Add</button>
            </form>
        </div>
    </div>
</div>