<div class="slide-panel-container" slide-panel="repos/sources/list">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/list" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/list">

            <h3>SOURCE REPOSITORIES</h3>

            <?php
            /**
             *  Print current sources repositories
             */
            \Controllers\Layout\Table\Render::render('repos/sources/list');

            /**
             *  Print imported GPG signing keys
             */
            \Controllers\Layout\Table\Render::render('repos/sources/gpgkeys'); ?>

            <br><br>
        </div>
    </div>
</div>
