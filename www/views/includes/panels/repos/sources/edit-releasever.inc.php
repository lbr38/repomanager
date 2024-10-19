<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/edit-releasever">
    <div class="slide-panel">
        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/edit-releasever" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/edit-releasever">
            <h3>EDIT RELEASE VERSION <?= strtoupper($releasever) ?></h3>

            <form class="source-repo-edit-releasever" source-id="<?= $sourceId ?>" releasever-id="<?= $releaseverId ?>">
                <h6>NAME</h6>
                <input type="text" class="releasever-param" param-name="name" value="<?= $releasever ?>" placeholder="Name" />

                <h6>DESCRIPTION</h6>
                <input type="text" class="releasever-param" param-name="description" value="<?= $description ?>" placeholder="Name" />

                <h6>GPG KEYS</h6>

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>
        </div>
    </div>
</div>
