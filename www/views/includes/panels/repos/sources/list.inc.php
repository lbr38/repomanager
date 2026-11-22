<?php
use \Controllers\Layout\Table\Render as TableRender; ?>

<div class="slide-panel-container" slide-panel="repos/sources/list">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/list" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/list">
            <h3>SOURCE REPOSITORIES</h3>

            <h6>ADD</h6>
            <p class="note">Import a predefined list or add a source repository manually.</p>

            <div class="flex column-gap-10 margin-top-5 margin-bottom-30">
                <button type="button" class="btn-medium-green get-panel-btn" panel="repos/sources/import">Import</button>
                <button type="button" class="btn-medium-tr get-panel-btn" panel="repos/sources/new">Manually add</button>
            </div>

            <?php
            // Print current sources repositories
            TableRender::render('repos/sources/list'); ?>

            <!-- Keys used to verify the source repositories signature, after the source repositories list -->
            <h6 class="margin-top-40">SIGNING KEYS</h6>
            <p class="note">GPG, RSA and signify public keys used to verify the signature of the source repositories.</p>

            <div class="flex column-gap-10 margin-top-5">
                <button type="button" class="btn-medium-tr get-panel-btn" panel="repos/sources/keys">Manage keys</button>
            </div>

            <br><br>
        </div>
    </div>
</div>
