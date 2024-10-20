<div class="slide-panel-container" slide-panel="repos/sources/list">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/list" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/list">

            <h3>SOURCE REPOSITORIES</h3>

            <p>Configure source repositories to mirror.</p>

            <h6>CURRENT SOURCE REPOSITORIES</h6>

            <br>
            <div class="flex column-gap-10">
                <button type="button" class="btn-medium-tr get-panel-btn" panel="repos/sources/new">Manually add</button>
                <button type="button" class="btn-medium-tr get-panel-btn" panel="repos/sources/import">Import</button>
            </div>
            <br><br>

            <?php
            /**
             *  Print current sources repositories
             */
            \Controllers\Layout\Table\Render::render('repos/sources/list'); ?>

            <h6>GPG SIGNING KEYS</h6>

            <h6>IMPORT A KEY</h6>
            <p class="note">Plain text format.</p>

            <form id="source-repo-add-key-form" autocomplete="off">
                <div class="flex align-content-center">
                    <textarea id="source-repo-add-key-textarea" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>
                    <button class="btn-xxsmall-green" title="Import">+</button>
                </div>
            </form>

            <?php
            /**
             *  Print imported GPG signing keys
             */
            \Controllers\Layout\Table\Render::render('repos/sources/gpgkeys'); ?>

            <br><br>
        </div>
    </div>
</div>
