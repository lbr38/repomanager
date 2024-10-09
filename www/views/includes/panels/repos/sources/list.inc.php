<div class="slide-panel-container" slide-panel="repos/sources/list">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/list" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/list">

            <h3>SOURCE REPOSITORIES</h3>

            <h6>CURRENT SOURCE REPOSITORIES</h6>
            <p class="note">Source repositories to be mirrored.</p>

            <div class="flex column-gap-10 margin-top-10 margin-bottom-15">
                <button type="button" class="btn-medium-blue get-panel-btn" panel="repos/sources/new">Manually add</button>
                <button type="button" class="btn-medium-blue get-panel-btn" panel="repos/sources/import">Import</button>
            </div>

            <?php
            /**
             *  Print current sources repositories
             */
            \Controllers\Layout\Table\Render::render('repos/sources/list'); ?>

            <h6>CURRENT GPG SIGNING KEYS</h6>
            <p class="note">All keys imported in Repomanager keyring.</p>

            <?php
            /**
             *  Print imported GPG signing keys
             */
            \Controllers\Layout\Table\Render::render('repos/sources/gpgkeys'); ?>

            <br><br>
        </div>
    </div>
</div>
