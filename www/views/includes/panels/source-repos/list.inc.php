<div class="slide-panel-container" slide-panel="sources-repos/list">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="sources-repos/list" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="sources-repos/list">

            <h3>SOURCE REPOSITORIES</h3>

            <p>Configure source repositories to mirror.</p>

            <?php
            /**
             *  Print current sources repositories
             */
            \Controllers\Layout\Table\Render::render('source-repos/list'); ?>

            <h5>GPG SIGNING KEYS</h5>

            <p>Import a GPG key</p>

            <br>

            <form id="source-repo-add-key-form" autocomplete="off">
                <div class="flex align-content-center">
                    <textarea id="source-repo-add-key-textarea" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>
                    <button class="btn-xxsmall-green" title="Import">+</button>
                </div>
            </form>

            <br>

            <?php
            /**
             *  Print imported GPG signing keys
             */
            \Controllers\Layout\Table\Render::render('source-repos/gpgkeys'); ?>

            <br><br>
        </div>
    </div>
</div>
